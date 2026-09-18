<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Retention\CalculateStreaks;
use App\LogActions;
use App\Models\JournalEntry;
use App\Models\TradingObjective;
use App\Support\Demo;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Ritual pre-mercado de 60 segundos (R5).
 *
 * Convive con el panel en vez de sustituirlo: una tarjeta arriba que se puede
 * ignorar. Interceptar el primer acceso del día conseguiría más adherencia y
 * también más gente aprendiendo a cerrar una pantalla sin leerla, y el panel
 * sigue siendo el sitio al que la mayoría viene a mirar sus números.
 *
 * Escribe en la entrada del diario de hoy —los mismos campos que la pantalla de
 * diario, `pre_market_*` y `daily_objectives`— para que las dos cuenten lo mismo
 * y el ritual sume a la racha.
 */
class PreMarketRitual extends Component
{
    use LogActions;

    /** Preguntas del ritual, en orden. */
    public const STEPS = 3;

    /** Mismos valores que la pantalla de diario: si no, no casarían. */
    public const MOODS = ['calm', 'anxious', 'confident', 'tired'];

    public bool $open = false;

    public int $step = 1;

    public ?string $mood = null;

    /** @var array<int, array{text:string, done:bool}> */
    public array $objectives = [];

    public string $notes = '';

    public bool $doneToday = false;

    public bool $postponed = false;

    public function mount(): void
    {
        $entry = $this->todayEntry();

        $this->doneToday = $entry !== null && $entry->pre_market_mood !== null;
        $this->postponed = (bool) session()->get($this->postponeKey(), false);
        $this->mood = $entry?->pre_market_mood;
        $this->notes = (string) ($entry?->pre_market_notes ?? '');
        $this->objectives = $this->initialObjectives($entry);
    }

    /**
     * ¿Se ofrece hoy?
     *
     * Pasada la hora de cierre configurada deja de aparecer: a las once de la
     * noche ya no hay ninguna sesión que preparar, y proponerlo entonces solo
     * enseña a ignorar la tarjeta.
     */
    public function shouldShow(): bool
    {
        if ($this->doneToday || $this->postponed) {
            return false;
        }

        return Auth::user()->nowInTimezone()->hour < (int) config('retention.pre_market.until_hour');
    }

    public function start(): void
    {
        $this->step = 1;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function next(): void
    {
        // El ánimo es obligatorio y es lo primero que se pide: dejar avanzar sin
        // elegirlo llevaba a un callejón sin salida en el último paso.
        if ($this->step === 1 && !in_array($this->mood, self::MOODS, true)) {
            $this->addError('mood', __('ritual.need_mood'));

            return;
        }

        $this->resetErrorBag('mood');
        $this->step = min(self::STEPS, $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    /** «Hoy no»: se recuerda en la sesión, no en base de datos. */
    public function postpone(): void
    {
        session()->put($this->postponeKey(), true);
        $this->postponed = true;
        $this->open = false;
    }

    public function addObjective(): void
    {
        if (count($this->objectives) >= 5) {
            return;
        }

        $this->objectives[] = ['text' => '', 'done' => false];
    }

    public function removeObjective(int $index): void
    {
        unset($this->objectives[$index]);
        $this->objectives = array_values($this->objectives);
    }

    public function finish(): void
    {
        if (!in_array($this->mood, self::MOODS, true)) {
            $this->step = 1;
            $this->addError('mood', __('ritual.need_mood'));
            $this->dispatch('show-alert', type: 'error', message: __('ritual.need_mood'));

            return;
        }

        if (Demo::active()) {
            $this->dispatch('show-alert', type: 'error', message: __('landing.demo.blocked'));
            $this->open = false;

            return;
        }

        $objectives = collect($this->objectives)
            ->map(fn (array $objective): array => [
                'text' => trim((string) $objective['text']),
                'done' => (bool) ($objective['done'] ?? false),
            ])
            ->filter(fn (array $objective): bool => $objective['text'] !== '')
            ->values()
            ->all();

        JournalEntry::updateOrCreate(
            ['user_id' => Auth::id(), 'date' => Auth::user()->nowInTimezone()->toDateString()],
            [
                'pre_market_mood' => $this->mood,
                'pre_market_notes' => $this->notes !== '' ? $this->notes : null,
                'daily_objectives' => $objectives,
            ]
        );

        // La racha de diario cuenta este día desde ya: dejarla cinco minutos
        // desactualizada haría dudar de si el ritual se ha guardado.
        CalculateStreaks::forget((int) Auth::id());

        $this->doneToday = true;
        $this->open = false;

        $this->insertLog(action: 'finish', form: 'PreMarketRitual', description: 'Ritual pre-mercado completado', type: 'info');
        $this->dispatch('show-alert', type: 'success', message: __('ritual.saved'));
    }

    private function todayEntry(): ?JournalEntry
    {
        return JournalEntry::where('user_id', Auth::id())
            ->whereDate('date', Auth::user()->nowInTimezone()->toDateString())
            ->first();
    }

    /**
     * Objetivos de partida: los que ya tenga el día, y si no, sus reglas activas.
     *
     * Copiarlas en lugar de enlazarlas es deliberado: el ritual fija el plan de
     * *hoy*, y cambiar mañana una regla maestra no debe reescribir lo que uno se
     * propuso ayer.
     */
    private function initialObjectives(?JournalEntry $entry): array
    {
        $saved = $entry?->daily_objectives ?? [];

        if (!empty($saved)) {
            return collect($saved)
                ->map(fn ($objective): array => [
                    'text' => (string) ($objective['text'] ?? ''),
                    'done' => (bool) ($objective['done'] ?? false),
                ])
                ->all();
        }

        $rules = TradingObjective::where('user_id', Auth::id())
            ->where('is_active', true)
            ->limit(5)
            ->pluck('text');

        if ($rules->isEmpty()) {
            return [['text' => '', 'done' => false]];
        }

        return $rules->map(fn (string $text): array => ['text' => $text, 'done' => false])->all();
    }

    private function postponeKey(): string
    {
        return 'tf_ritual_postponed_' . Auth::user()->nowInTimezone()->toDateString();
    }

    public function render()
    {
        return view('livewire.pre-market-ritual');
    }
}
