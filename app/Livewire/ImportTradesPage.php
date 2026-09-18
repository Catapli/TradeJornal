<?php

declare(strict_types=1);

namespace App\Livewire;

use App\LogActions;
use App\Models\Account;
use App\Models\ImportProfile;
use App\Models\Strategy;
use App\Services\Import\ImportPreset;
use App\Services\Import\ImportReport;
use App\Services\Import\TradeFileReader;
use App\Services\Import\TradeImporter;
use App\Services\Import\TradeRowMapper;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Asistente de importación de historiales.
 *
 * Cuatro pasos: subir → emparejar columnas → vista previa → resultado.
 *
 * Nota de diseño importante: **las filas del fichero no se guardan en el estado
 * del componente**. Livewire serializa las propiedades públicas en cada petición,
 * y un histórico de 2.000 operaciones convertiría cada clic en un payload de
 * varios megas. Lo que se conserva es el fichero temporal, y las filas se releen
 * en una propiedad computada, que Livewire cachea dentro de cada petición.
 */
class ImportTradesPage extends Component
{
    use LogActions;
    use WithFileUploads;

    /** Extensiones aceptadas. La detección de MIME de los CSV es poco fiable. */
    private const EXTENSIONS = ['csv', 'txt', 'tsv', 'html', 'htm'];

    private const MAX_KB = 8192;

    /** Filas que se enseñan en la vista previa. */
    private const PREVIEW_ROWS = 8;

    public string $step = 'upload';

    public ?int $accountId = null;

    public $file;

    public string $presetKey = 'generic';

    /** @var array<int, string> */
    public array $headers = [];

    /** @var array<string, string|null> */
    public array $mapping = [];

    public bool $pnlIncludesFees = true;

    public string $decimalMode = 'auto';

    public ?int $strategyId = null;

    public bool $recalculateBalance = true;

    public bool $saveProfile = false;

    public string $profileName = '';

    public ?int $loadedProfileId = null;

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public ?string $readError = null;

    // ─────────────────────────────────────────────────────────────
    // Datos de apoyo
    // ─────────────────────────────────────────────────────────────

    #[Computed]
    public function accounts()
    {
        return Account::where('user_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'currency', 'initial_balance']);
    }

    #[Computed]
    public function strategies()
    {
        return Strategy::where('user_id', Auth::id())->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function profiles()
    {
        return ImportProfile::where('user_id', Auth::id())->orderBy('name')->get();
    }

    #[Computed]
    public function presets(): array
    {
        return ImportPreset::all();
    }

    /**
     * Lee el fichero temporal. Cacheado por petición gracias a #[Computed].
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>, format: string}|null
     */
    #[Computed]
    public function parsed(): ?array
    {
        if (!$this->file) {
            return null;
        }

        try {
            return app(TradeFileReader::class)->read(
                $this->file->getRealPath(),
                $this->file->getClientOriginalName(),
            );
        } catch (Throwable $e) {
            $this->readError = $e->getMessage();

            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Paso 1 · Subida
    // ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->accountId = $this->accounts->first()?->id;
    }

    public function analyze(): void
    {
        $this->readError = null;

        $this->validate([
            'accountId' => 'required|integer',
            'file' => 'required|file|max:' . self::MAX_KB,
        ]);

        if (!$this->ownedAccount()) {
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('import.errors.account_not_found')]);

            return;
        }

        $extension = mb_strtolower($this->file->getClientOriginalExtension());

        if (!in_array($extension, self::EXTENSIONS, true)) {
            $this->addError('file', __('import.upload.file_help'));

            return;
        }

        $parsed = $this->parsed();

        if ($parsed === null) {
            $this->addError('file', __('import.errors.read_failed', ['message' => $this->readError ?? '']));

            return;
        }

        $this->headers = $parsed['headers'];

        // El informe HTML de MetaTrader ya sale con cabeceras canónicas del lector,
        // así que el emparejamiento es la identidad y no hay nada que adivinar.
        if ($parsed['format'] === 'metatrader_html') {
            $this->presetKey = 'metatrader';
            $this->mapping = $this->identityMapping($parsed['headers']);
        } else {
            $preset = ImportPreset::detect($parsed['headers']);
            $this->presetKey = $preset->key;
            $this->mapping = $preset->guessMapping($parsed['headers']);
        }

        $this->step = 'map';

        $this->insertLog(
            action: 'Fichero analizado',
            form: 'ImportTradesPage',
            description: "Formato {$parsed['format']}, preset {$this->presetKey}, " . count($parsed['rows']) . ' filas',
        );
    }

    // ─────────────────────────────────────────────────────────────
    // Paso 2 · Emparejamiento
    // ─────────────────────────────────────────────────────────────

    /** Al cambiar de plataforma a mano se rehace la propuesta de columnas. */
    public function updatedPresetKey(): void
    {
        if ($this->headers !== []) {
            $this->mapping = ImportPreset::find($this->presetKey)->guessMapping($this->headers);
        }
    }

    public function applyProfile(): void
    {
        $profile = ImportProfile::where('user_id', Auth::id())->find($this->loadedProfileId);

        if (!$profile) {
            return;
        }

        $this->presetKey = $profile->preset;
        $this->pnlIncludesFees = $profile->pnl_includes_fees;
        $this->decimalMode = $profile->decimal_mode;

        // Solo se aplican las columnas que existan en este fichero: un perfil
        // antiguo no debe dejar el mapeo apuntando a cabeceras inexistentes.
        foreach ($profile->mapping as $field => $header) {
            $this->mapping[$field] = in_array($header, $this->headers, true) ? $header : null;
        }
    }

    /** @return array<int, string> campos obligatorios todavía sin emparejar */
    public function missingRequired(): array
    {
        $missing = [];

        foreach (ImportPreset::FIELDS as $field => $meta) {
            if ($meta['required'] && empty($this->mapping[$field])) {
                $missing[] = __('import.fields.' . $field);
            }
        }

        return $missing;
    }

    public function goToPreview(): void
    {
        if ($this->missingRequired() !== []) {
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => __('import.map.missing_required', ['fields' => implode(', ', $this->missingRequired())]),
            ]);

            return;
        }

        $this->step = 'preview';
    }

    // ─────────────────────────────────────────────────────────────
    // Paso 3 · Vista previa
    // ─────────────────────────────────────────────────────────────

    /**
     * Aplica el mapeo a las primeras filas para que el usuario vea el resultado
     * antes de escribir nada.
     *
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>, total: int}
     */
    #[Computed]
    public function preview(): array
    {
        $parsed = $this->parsed();

        if ($parsed === null) {
            return ['rows' => [], 'errors' => [], 'total' => 0];
        }

        $mapper = $this->mapper();
        $rows = [];
        $errors = [];
        $number = 1;

        foreach ($parsed['rows'] as $row) {
            $number++;

            if (count($rows) >= self::PREVIEW_ROWS && count($errors) >= 3) {
                break;
            }

            $result = $mapper->map($row);

            if ($result['data'] === null) {
                if (count($errors) < 3) {
                    $errors[] = ['row' => $number, 'errors' => $result['errors']];
                }

                continue;
            }

            if (count($rows) < self::PREVIEW_ROWS) {
                $rows[] = $result['data'];
            }
        }

        return ['rows' => $rows, 'errors' => $errors, 'total' => count($parsed['rows'])];
    }

    // ─────────────────────────────────────────────────────────────
    // Paso 4 · Importar
    // ─────────────────────────────────────────────────────────────

    public function import(TradeImporter $importer): void
    {
        $account = $this->ownedAccount();

        if (!$account) {
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('import.errors.account_not_found')]);

            return;
        }

        $parsed = $this->parsed();

        if ($parsed === null) {
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('import.errors.read_failed', ['message' => $this->readError ?? ''])]);

            return;
        }

        try {
            $report = $importer->import(
                $account,
                $parsed['rows'],
                $this->mapper(),
                $this->ownedStrategyId(),
                $this->recalculateBalance,
            );
        } catch (Throwable $e) {
            $this->logError($e, 'import', 'ImportTradesPage', 'Fallo importando historial');
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('import.errors.unexpected')]);

            return;
        }

        if ($this->saveProfile && trim($this->profileName) !== '') {
            $this->persistProfile();
        }

        $this->result = $this->summarize($report);
        $this->step = 'done';

        $this->insertLog(
            action: 'Historial importado',
            form: 'ImportTradesPage',
            description: "Cuenta {$account->id}: {$report->imported} importadas, {$report->skipped} omitidas, {$report->failedCount} con error",
        );
    }

    public function reset_(): void
    {
        $this->reset(['file', 'headers', 'mapping', 'result', 'readError', 'saveProfile', 'profileName', 'loadedProfileId']);
        $this->step = 'upload';
    }

    // ─────────────────────────────────────────────────────────────

    private function mapper(): TradeRowMapper
    {
        return new TradeRowMapper(
            ImportPreset::find($this->presetKey),
            $this->mapping,
            $this->pnlIncludesFees,
            $this->decimalMode,
        );
    }

    private function ownedAccount(): ?Account
    {
        return Account::where('user_id', Auth::id())->find($this->accountId);
    }

    private function ownedStrategyId(): ?int
    {
        if ($this->strategyId === null) {
            return null;
        }

        return Strategy::where('user_id', Auth::id())->whereKey($this->strategyId)->value('id');
    }

    /** @param array<int, string> $headers */
    private function identityMapping(array $headers): array
    {
        $mapping = [];

        foreach (array_keys(ImportPreset::FIELDS) as $field) {
            $mapping[$field] = in_array($field, $headers, true) ? $field : null;
        }

        return $mapping;
    }

    private function persistProfile(): void
    {
        ImportProfile::updateOrCreate(
            ['user_id' => Auth::id(), 'name' => trim($this->profileName)],
            [
                'preset' => $this->presetKey,
                'mapping' => array_filter($this->mapping, static fn ($header) => $header !== null),
                'pnl_includes_fees' => $this->pnlIncludesFees,
                'decimal_mode' => $this->decimalMode,
            ],
        );

        unset($this->profiles);
    }

    /** @return array<string, mixed> */
    private function summarize(ImportReport $report): array
    {
        return [
            'imported' => $report->imported,
            'skipped' => $report->skipped,
            'failed' => $report->failedCount,
            'errors' => $report->failed,
            'newSymbols' => $report->newSymbols,
        ];
    }

    public function render()
    {
        return view('livewire.import-trades-page');
    }
}
