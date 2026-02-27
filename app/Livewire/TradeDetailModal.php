<?php

namespace App\Livewire;

use App\LogActions;
use App\WithAiLimits;
use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class TradeDetailModal extends Component
{
    use WithFileUploads;
    use WithAiLimits;
    use LogActions;

    // ── PAYLOAD LIVEWIRE (lo que viaja en cada request) ──────────────────────
    // ANTES: selectedTrade = Eloquent Model completo → ~50-100KB por request
    // AHORA: selectedTradeId = integer               → ~4 bytes por request
    // El modelo se rehidrata vía #[Computed] sin tocar el snapshot.

    #[Locked] public ?int    $selectedTradeId   = null;
    #[Locked] public ?int    $prevTradeId       = null;
    #[Locked] public ?int    $nextTradeId       = null;
    #[Locked] public array   $contextTradeIds   = [];
    #[Locked] public ?string $currentScreenshot = null;

    // Propiedades que el usuario sí puede mutar desde el frontend
    public string $notes              = '';
    public mixed  $uploadedScreenshot = null;
    public bool   $isAnalyzingTrade   = false;


    // ─────────────────────────────────────────────────────────────────────────
    // COMPUTED PROPERTIES
    // Se ejecutan una vez por request, se cachean en memoria, nunca en payload.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mejora 12: El trade completo con relaciones, calculado por request.
     *
     * Flujo: selectedTradeId (int en payload) → trade() (query en servidor)
     * Para invalidar la cache mid-request (tras un update): unset($this->trade)
     */
    #[Computed]
    public function trade(): ?Trade
    {
        if (!$this->selectedTradeId) return null;

        return Trade::with(['account', 'tradeAsset', 'mistakes'])
            ->whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
            ->find($this->selectedTradeId);
    }

    /**
     * Mejora 9: getAiCreditsLeft() deja de ejecutarse en cada re-render.
     * Con #[Computed], Livewire lo cachea dentro del mismo ciclo de request.
     * En el Blade: {{ $this->aiCreditsLeft }}
     */
    #[Computed]
    public function aiCreditsLeft(): int
    {
        return $this->getAiCreditsLeft();
    }


    // ─────────────────────────────────────────────────────────────────────────
    // MÉTODOS PÚBLICOS
    // ─────────────────────────────────────────────────────────────────────────

    public function loadTradeData(int $tradeId, array $contextIds = []): void
    {
        try {
            // Reseteamos a primitivos. Al poner selectedTradeId = null,
            // Livewire invalida automáticamente el computed cache de trade().
            $this->selectedTradeId  = null;
            $this->prevTradeId      = null;
            $this->nextTradeId      = null;
            $this->isAnalyzingTrade = false;
            $this->notes            = '';
            $this->uploadedScreenshot = null;
            $this->contextTradeIds  = $contextIds;

            $this->loadTrade($tradeId);
        } catch (\Throwable $e) {
            $this->logError($e, 'loadTradeData', 'TradeDetailModal', "Trade ID: {$tradeId}");
        } finally {
            $this->dispatch('trade-data-loaded');
        }
    }

    public function goToPrev(): void
    {
        try {
            if ($this->prevTradeId) $this->loadTrade($this->prevTradeId);
        } catch (\Throwable $e) {
            $this->logError($e, 'goToPrev', 'TradeDetailModal', "Prev ID: {$this->prevTradeId}");
        } finally {
            $this->dispatch('trade-data-loaded');
        }
    }

    public function goToNext(): void
    {
        try {
            if ($this->nextTradeId) $this->loadTrade($this->nextTradeId);
        } catch (\Throwable $e) {
            $this->logError($e, 'goToNext', 'TradeDetailModal', "Next ID: {$this->nextTradeId}");
        } finally {
            $this->dispatch('trade-data-loaded');
        }
    }

    /**
     * Mejora 8 aplicada: sin usleep, isSavingNotes se gestiona en finally.
     * Mejora 12 aplicada: update directo por ID, sin rehidratar el modelo completo.
     */
    public function saveNotes(string $notes = ''): void
    {
        try {
            if (!$this->selectedTradeId) return;


            // Sincronizamos $this->notes por consistencia interna del componente
            $this->notes = $notes;
            // whereKey es semánticamente más claro que where('id', ...) 
            // y evita cargar el modelo con relaciones solo para un update de un campo.
            Trade::whereKey($this->selectedTradeId)->update(['notes' => $this->notes]);

            // Invalidamos el computed cache para que el próximo acceso traiga datos frescos.
            // Esto garantiza que si otra parte del blade lee $this->trade->notes, sea consistente.
            unset($this->trade);

            $this->dispatch('trade-updated');
        } catch (\Throwable $e) {
            $this->logError($e, 'saveNotes', 'TradeDetailModal', "Trade ID: {$this->selectedTradeId}");
        }
    }

    /**
     * Mejora 7: getMimeType() dinámico sobre el UploadedFile.
     * Mejora 11: update directo por ID + invalidación de computed cache.
     *            Ya no se hace un re-query completo con relaciones.
     */
    public function updatedUploadedScreenshot(): void
    {
        try {
            $this->validate(['uploadedScreenshot' => 'required|image|max:10240']);

            if (!$this->selectedTradeId) return;

            // Mime real del archivo subido (inspección binaria, no extensión)
            $mimeType = $this->uploadedScreenshot->getMimeType();
            $path     = $this->uploadedScreenshot->store('screenshots', 'public');

            // Limpieza del archivo anterior usando el primitivo local (sin re-query)
            if ($this->currentScreenshot && Storage::disk('public')->exists($this->currentScreenshot)) {
                Storage::disk('public')->delete($this->currentScreenshot);
            }

            // Update directo por ID
            Trade::whereKey($this->selectedTradeId)->update(['screenshot' => $path]);

            // Actualizar el primitivo local para que Alpine y el Blade reflejen el cambio
            $this->currentScreenshot = $path;

            // Invalidar computed cache
            unset($this->trade);

            $this->reset('uploadedScreenshot');
            $this->dispatch('screenshot-updated', mimeType: $mimeType);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Re-lanzar para que Livewire gestione @error en el Blade
        } catch (\Throwable $e) {
            $this->logError($e, 'updatedUploadedScreenshot', 'TradeDetailModal', "Trade ID: {$this->selectedTradeId}");
            $this->reset('uploadedScreenshot');
        }
    }

    /**
     * Mejora 6: config() en lugar de env().
     * Mejora 7: mime_content_type() para la imagen ya almacenada en disco.
     * Mejora 12: accede al computed $this->trade; invalida cache post-update.
     */
    public function analyzeIndividualTrade(): void
    {
        if (!$this->selectedTradeId) return;
        if (!$this->checkAiLimit()) return;

        $this->isAnalyzingTrade = true;

        try {
            // Una sola query cacheada en este request. Si ya se llamó antes, 
            // Livewire devuelve el resultado en memoria sin re-query.
            $trade = $this->trade;
            if (!$trade) return;

            $futureAnalysis = $this->analyzePostTradeContext($trade);

            $contextoDatos = implode("\n", [
                __('ai.labels.asset')      . ": {$trade->tradeAsset->name}",
                __('ai.labels.type')       . ': ' . strtoupper($trade->direction),
                __('ai.labels.entry')      . ": {$trade->entry_price} | " . __('ai.labels.exit') . ": {$trade->exit_price}",
                __('ai.labels.result')     . ": {$trade->pnl} (Lots: {$trade->size})",
                __('ai.labels.duration')   . ": {$trade->duration_minutes} min",
                __('ai.labels.efficiency') . ": MAE: {$trade->mae_price} | MFE: {$trade->mfe_price}",
                __('ai.labels.future')     . ": {$futureAnalysis}",
            ]);

            $parts = [['text' => __('ai.audit_prompt', ['context' => $contextoDatos])]];

            // Adjuntar imagen con mime real del disco
            if ($trade->screenshot && Storage::disk('public')->exists($trade->screenshot)) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => mime_content_type(Storage::disk('public')->path($trade->screenshot)),
                        'data'      => base64_encode(Storage::disk('public')->get($trade->screenshot)),
                    ],
                ];
            }

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key='
                        . config('services.gemini.key'),
                    [
                        'contents'         => [['parts' => $parts]],
                        'generationConfig' => ['temperature' => 0.4],
                    ]
                );

            if ($response->successful()) {
                $analysisText = $response->json()['candidates'][0]['content']['parts'][0]['text'];

                // Persistir en BD y consumir crédito solo si Gemini respondió OK
                Trade::whereKey($this->selectedTradeId)->update(['ai_analysis' => $analysisText]);
                $this->consumeAiCredit();

                // Doble invalidación: trade (para mostrar ai_analysis) y aiCreditsLeft (contador)
                unset($this->trade);
                unset($this->aiCreditsLeft);
            } else {
                $this->logError(
                    new \RuntimeException($response->body()),
                    'analyzeIndividualTrade',
                    'TradeDetailModal',
                    "Gemini {$response->status()} - Trade ID: {$trade->id}"
                );
                $this->dispatch('notify', __('labels.error_gemini') . $response->status());
            }
        } catch (\Throwable $e) {
            $this->logError($e, 'analyzeIndividualTrade', 'TradeDetailModal', "Trade ID: {$this->selectedTradeId}");
            $this->dispatch('notify', __('labels.ai_error_generic'));
        } finally {
            $this->isAnalyzingTrade = false;
        }
    }

    public function resetModal(): void
    {
        $this->reset([
            'selectedTradeId',   // ← Ya no es selectedTrade
            'prevTradeId',
            'nextTradeId',
            'notes',
            'isAnalyzingTrade',
            'isSavingNotes',
            'contextTradeIds',
            'currentScreenshot',
            'uploadedScreenshot',
        ]);

        // Limpiar computed cache explícitamente al resetear
        unset($this->trade);
        unset($this->aiCreditsLeft);
    }


    // ─────────────────────────────────────────────────────────────────────────
    // MÉTODOS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    private function loadTrade(int $tradeId): void
    {
        // Asignar el ID activa el computed: el próximo acceso a $this->trade
        // ejecutará la query con el nuevo ID.
        $this->selectedTradeId = $tradeId;

        $trade = $this->trade; // Una query, cacheada para el resto del request

        if (!$trade) {
            // Trade no existe o no pertenece al usuario (scope de seguridad en computed)
            $this->selectedTradeId = null;
            return;
        }

        // Extraer primitivos al estado de Livewire.
        // Solo strings/nulls simples → payload mínimo.
        $this->notes             = $trade->notes ?? '';
        $this->currentScreenshot = $trade->screenshot;

        // Trade como parámetro explícito: evita re-acceder al computed innecesariamente
        $this->calculateNavigation($trade);

        $this->dispatch(
            'trade-selected',
            path: $trade->chart_data_path,
            entry: $trade->entry_price,
            exit: $trade->exit_price,
            direction: $trade->direction
        );
    }

    /**
     * Recibe el $trade como parámetro para no re-ejecutar el computed
     * (ya fue cacheado en loadTrade() en el mismo request).
     */
    private function calculateNavigation(Trade $trade): void
    {
        $currentId = $trade->id;

        // Opción A: Contexto en memoria — O(1)
        if (!empty($this->contextTradeIds)) {
            $currentIndex = array_search($currentId, $this->contextTradeIds);
            if ($currentIndex !== false) {
                $this->prevTradeId = $this->contextTradeIds[$currentIndex + 1] ?? null;
                $this->nextTradeId = $this->contextTradeIds[$currentIndex - 1] ?? null;
                return;
            }
        }

        // Opción B: Fallback SQL (cubierto por índice compuesto [exit_time, id])
        $currentDate = $trade->exit_time->format('Y-m-d');

        $baseQuery = fn() => Trade::whereHas('account', fn($q) => $q->where('user_id', Auth::id()))
            ->whereDate('exit_time', $currentDate)
            ->select('id');

        $prev = $baseQuery()
            ->where(
                fn($q) => $q
                    ->where('exit_time', '<', $trade->exit_time)
                    ->orWhere(
                        fn($q2) => $q2
                            ->where('exit_time', $trade->exit_time)
                            ->where('id', '<', $currentId)
                    )
            )
            ->orderByDesc('exit_time')->orderByDesc('id')
            ->first();

        $next = $baseQuery()
            ->where(
                fn($q) => $q
                    ->where('exit_time', '>', $trade->exit_time)
                    ->orWhere(
                        fn($q2) => $q2
                            ->where('exit_time', $trade->exit_time)
                            ->where('id', '>', $currentId)
                    )
            )
            ->orderBy('exit_time')->orderBy('id')
            ->first();

        $this->prevTradeId = $prev?->id;
        $this->nextTradeId = $next?->id;
    }

    private function analyzePostTradeContext(Trade $trade): string
    {
        if (!$trade->chart_data_path || !Storage::disk('public')->exists($trade->chart_data_path)) {
            return __('labels.no_data_market');
        }

        $chartData = json_decode(Storage::disk('public')->get($trade->chart_data_path), true);
        $candles   = $chartData['timeframes']['5m'] ?? ($chartData['timeframes']['1m'] ?? []);

        if (empty($candles)) return __('labels.data_candles_not_enough');

        $exitTimestamp         = Carbon::parse($trade->exit_time)->timestamp;
        $entryPrice            = (float) $trade->entry_price;
        $isLong                = in_array(strtoupper($trade->direction), ['LONG', 'BUY']);
        $maxFavorableAfterExit = 0;
        $foundExit             = false;
        $candlesChecked        = 0;

        foreach ($candles as $candle) {
            if ($candle['time'] < $exitTimestamp) continue;

            $foundExit = true;
            $candlesChecked++;

            $delta = $isLong ? ($candle['high'] - $entryPrice) : ($entryPrice - $candle['low']);
            if ($delta > $maxFavorableAfterExit) $maxFavorableAfterExit = $delta;
            if ($candlesChecked >= 30) break;
        }

        if (!$foundExit) return __('labels.not_data_close');

        $originalMfe = abs(($trade->mfe_price ?? 0) - $entryPrice);
        $threshold   = $originalMfe > 0 ? ($originalMfe * 1.5) : ($entryPrice * 0.0005);
        $pointsMoved = number_format($maxFavorableAfterExit, 5);

        return $maxFavorableAfterExit > $threshold
            ? __('labels.liquidity_sweep', ['pointsMoved' => $pointsMoved])
            : __('labels.no_good_movement');
    }

    public function render()
    {
        return view('livewire.trade-detail-modal');
    }
}
