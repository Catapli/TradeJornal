<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Retention\BuildWeeklySummary;
use App\Actions\Retention\PickReviewTrades;
use App\Concerns\RequiresProAccess;
use App\LogActions;
use App\Models\WeeklyReview;
use App\Support\Demo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Revisión semanal guiada (R2).
 *
 * La aplicación elige las seis operaciones y hace las mismas tres preguntas de
 * cada una, semana tras semana. La gracia no está en las respuestas sueltas sino
 * en la serie: al cerrarla se congelan las métricas de esa semana y el archivo
 * permite ver si la disciplina mejora o solo cambia el mercado.
 *
 * Módulo PRO. `RequiresProAccess` corta las acciones en servidor; la vista se
 * renderiza igualmente porque `<x-pro-gate>` la usa de escaparate.
 */
class WeeklyReviewPage extends Component
{
    use LogActions;
    use RequiresProAccess;

    /** Lunes de la semana que se está revisando (Y-m-d). */
    public string $weekStart = '';

    /** ['trade_12' => ['plan' => 'yes', 'trigger' => '…', 'change' => '…']] */
    public array $answers = [];

    public string $takeaway = '';

    /** Respuestas admitidas en la primera pregunta. */
    public const PLAN_ANSWERS = ['yes', 'partly', 'no'];

    public function mount(): void
    {
        $this->weekStart = self::defaultWeek(Auth::user()->nowInTimezone())->toDateString();
        $this->loadReview();
    }

    /**
     * La semana que toca revisar.
     *
     * El domingo es la que acaba de terminar hoy —es cuando sale el correo—; el
     * resto de la semana, la anterior. Nunca la semana en curso: revisar algo a
     * medias no lleva a ninguna conclusión.
     */
    public static function defaultWeek(CarbonImmutable $today): CarbonImmutable
    {
        return $today->isSunday()
            ? $today->startOfWeek()
            : $today->startOfWeek()->subWeek();
    }

    // ── Navegación ───────────────────────────────────────────────

    public function previousWeek(): void
    {
        $this->weekStart = CarbonImmutable::parse($this->weekStart)->subWeek()->toDateString();
        $this->loadReview();
    }

    public function nextWeek(): void
    {
        $next = CarbonImmutable::parse($this->weekStart)->addWeek();

        if ($next->greaterThan(Auth::user()->nowInTimezone()->startOfWeek())) {
            return;
        }

        $this->weekStart = $next->toDateString();
        $this->loadReview();
    }

    public function openWeek(string $weekStart): void
    {
        $this->weekStart = CarbonImmutable::parse($weekStart)->startOfWeek()->toDateString();
        $this->loadReview();
    }

    public function canGoForward(): bool
    {
        return CarbonImmutable::parse($this->weekStart)->addWeek()
            ->lessThanOrEqualTo(Auth::user()->nowInTimezone()->startOfWeek());
    }

    // ── Datos ────────────────────────────────────────────────────

    #[Computed]
    public function summary(): array
    {
        return app(BuildWeeklySummary::class)->execute(Auth::user(), CarbonImmutable::parse($this->weekStart));
    }

    /**
     * Las operaciones a revisar.
     *
     * Una revisión cerrada enseña siempre las mismas seis, aunque hoy los
     * extremos de esa semana fueran otros: si no, el archivo dejaría de casar
     * con lo que el usuario contestó.
     */
    #[Computed]
    public function trades(): Collection
    {
        $picker = app(PickReviewTrades::class);
        $review = $this->review;

        if ($review?->isCompleted()) {
            return $picker->byIds(Auth::user(), $this->tradeIdsFromAnswers($review->answers ?? []));
        }

        return $picker->execute(Auth::user(), CarbonImmutable::parse($this->weekStart));
    }

    #[Computed]
    public function review(): ?WeeklyReview
    {
        return WeeklyReview::where('user_id', Auth::id())
            ->whereDate('week_start', $this->weekStart)
            ->first();
    }

    /** Semanas ya cerradas, de la más reciente a la más antigua. */
    #[Computed]
    public function history(): Collection
    {
        return WeeklyReview::where('user_id', Auth::id())
            ->whereNotNull('completed_at')
            ->orderByDesc('week_start')
            ->limit(8)
            ->get();
    }

    /** Cuántas de las seis están contestadas (basta la primera pregunta). */
    public function answered(): int
    {
        return $this->trades
            ->filter(fn ($trade): bool => !empty($this->answers["trade_{$trade->id}"]['plan']))
            ->count();
    }

    public function isCompleted(): bool
    {
        return $this->review?->isCompleted() ?? false;
    }

    /**
     * Abre el detalle de la operación en el modal global.
     *
     * Contestar «¿estaba en tu plan?» de memoria, cinco días después, no vale
     * gran cosa: hace falta volver a ver el gráfico, las notas y los errores
     * marcados. Se le pasan las seis de la revisión como contexto, así que las
     * flechas del modal recorren justo esas.
     */
    public function openTradeDetail(int $tradeId): void
    {
        $ids = $this->trades->pluck('id')->map(fn ($id): int => (int) $id)->all();

        // La operación tiene que ser una de las que se están revisando. Las seis
        // ya vienen filtradas por las cuentas del usuario, pero el id llega del
        // navegador y aquí es donde se comprueba.
        if (!in_array($tradeId, $ids, true)) {
            return;
        }

        $this->dispatch('open-trade-detail', tradeId: $tradeId, tradeIds: $ids);
    }

    // ── Guardado ─────────────────────────────────────────────────

    public function save(): void
    {
        $this->persist(complete: false);
    }

    public function complete(): void
    {
        if ($this->answered() < $this->trades->count()) {
            $this->dispatch('show-alert', type: 'error', message: __('weekly.review.incomplete'));

            return;
        }

        $this->persist(complete: true);
    }

    private function persist(bool $complete): void
    {
        if (Demo::active()) {
            $this->dispatch('show-alert', type: 'error', message: __('landing.demo.blocked'));

            return;
        }

        $this->answers = $this->sanitizedAnswers();

        WeeklyReview::updateOrCreate(
            ['user_id' => Auth::id(), 'week_start' => $this->weekStart],
            [
                'answers' => $this->answers,
                'takeaway' => $this->takeaway !== '' ? $this->takeaway : null,
                // Las métricas se congelan al cerrar: reclasificar una operación
                // meses después no debe reescribir la historia de esa semana.
                'stats' => $complete ? $this->snapshot() : $this->review?->stats,
                'completed_at' => $complete ? now() : $this->review?->completed_at,
            ]
        );

        unset($this->review, $this->history, $this->trades);

        $this->insertLog(
            action: $complete ? 'complete' : 'save',
            form: 'WeeklyReviewPage',
            description: "Revisión semanal de la semana del {$this->weekStart}",
            type: 'info'
        );

        $this->dispatch('show-alert', type: 'success', message: __($complete ? 'weekly.review.completed' : 'weekly.review.saved'));
    }

    /** Solo las respuestas de las operaciones que de verdad se están revisando. */
    private function sanitizedAnswers(): array
    {
        $valid = $this->trades->map(fn ($trade): string => "trade_{$trade->id}")->all();
        $clean = [];

        foreach ($valid as $key) {
            $answer = $this->answers[$key] ?? [];

            $plan = $answer['plan'] ?? null;

            $clean[$key] = [
                'plan' => in_array($plan, self::PLAN_ANSWERS, true) ? $plan : null,
                'trigger' => mb_substr(trim((string) ($answer['trigger'] ?? '')), 0, 1000),
                'change' => mb_substr(trim((string) ($answer['change'] ?? '')), 0, 1000),
            ];
        }

        return $clean;
    }

    /** Fotografía de la semana en el momento de cerrarla. */
    private function snapshot(): array
    {
        $summary = $this->summary;

        return [
            'trades' => $summary['trades'],
            'pnl' => $summary['pnl'],
            'win_rate' => $summary['win_rate'],
            'journal_days' => $summary['journal_days'],
            'discipline' => $summary['discipline'],
            'mistakes' => count($summary['mistakes']),
        ];
    }

    private function loadReview(): void
    {
        unset($this->review, $this->trades, $this->summary);

        $review = $this->review;

        $this->answers = $review?->answers ?? [];
        $this->takeaway = (string) ($review?->takeaway ?? '');
    }

    /** @return array<int> */
    private function tradeIdsFromAnswers(array $answers): array
    {
        return collect(array_keys($answers))
            ->map(fn (string $key): int => (int) str_replace('trade_', '', $key))
            ->filter()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.weekly-review-page');
    }
}
