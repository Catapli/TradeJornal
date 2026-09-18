<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Mentor\BuildTraderProfile;
use App\Actions\Mentor\ProposeMonthlyGoal;
use App\Actions\Mentor\TrackMonthlyGoal;
use App\Concerns\RequiresProAccess;
use App\LogActions;
use App\Models\ImprovementGoal;
use App\Support\Demo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Mentor con memoria (Fase 6 · P5).
 *
 * El resto de la aplicación audita operaciones una a una. Esta pantalla hace lo
 * contrario: mira los últimos seis meses, dice qué se repite y pide **una sola
 * cosa** para este mes, con la cifra de la que se viene delante.
 *
 * El objetivo se fija una vez y no se puede editar ni borrar. Es deliberado: un
 * objetivo que se cambia a mitad de mes cuando va mal no es un objetivo, y el
 * histórico de aciertos y fallos es justo lo que le da sentido a todo esto.
 *
 * Módulo PRO. `RequiresProAccess` corta las acciones en servidor; la vista se
 * renderiza igualmente porque `<x-pro-gate>` la usa de escaparate.
 *
 * @property-read array<string, mixed> $profile
 * @property-read ImprovementGoal|null $goal
 * @property-read array<string, mixed>|null $proposal
 * @property-read array<string, mixed>|null $progress
 * @property-read \Illuminate\Support\Collection<int, ImprovementGoal> $history
 */
class MentorPage extends Component
{
    use LogActions;
    use RequiresProAccess;

    /** El diálogo de confirmación al fijar el objetivo. */
    public bool $confirming = false;

    public function mount(): void
    {
        // Al entrar se saldan los meses cerrados. Es el único momento en que se
        // hace: no hay cron para esto, y un objetivo sin cerrar no es seguimiento.
        app(TrackMonthlyGoal::class)->closeFinished((int) Auth::id());
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function profile(): array
    {
        return app(BuildTraderProfile::class)->execute((int) Auth::id());
    }

    /** El objetivo de este mes, si ya está fijado. */
    #[Computed]
    public function goal(): ?ImprovementGoal
    {
        return ImprovementGoal::where('user_id', Auth::id())
            ->whereDate('month', CarbonImmutable::today()->startOfMonth())
            ->with('mistake')
            ->first();
    }

    /** @return array<string, mixed>|null */
    #[Computed]
    public function proposal(): ?array
    {
        return $this->goal
            ? null
            : app(ProposeMonthlyGoal::class)->execute((int) Auth::id(), $this->profile);
    }

    /** @return array<string, mixed>|null */
    #[Computed]
    public function progress(): ?array
    {
        return $this->goal
            ? app(TrackMonthlyGoal::class)->progress($this->goal)
            : null;
    }

    /**
     * Objetivos de meses ya cerrados, del más reciente al más antiguo.
     *
     * @return Collection<int, ImprovementGoal>
     */
    #[Computed]
    public function history(): Collection
    {
        return ImprovementGoal::where('user_id', Auth::id())
            ->where('status', '!=', ImprovementGoal::STATUS_ACTIVE)
            ->with('mistake')
            ->orderByDesc('month')
            ->limit(12)
            ->get();
    }

    public function setGoal(): void
    {
        if (Demo::active()) {
            $this->dispatch('show-alert', type: 'error', message: __('landing.demo.blocked'));

            return;
        }

        $propuesta = $this->proposal;

        if (!$propuesta) {
            return;
        }

        // El índice único (user_id, month) es la última defensa contra dos
        // objetivos del mismo mes si llegan dos peticiones a la vez.
        $objetivo = ImprovementGoal::firstOrCreate(
            ['user_id' => Auth::id(), 'month' => $propuesta['month']],
            [
                'mistake_id' => $propuesta['mistake_id'],
                'statement' => $this->statement($propuesta),
                'baseline' => $propuesta['baseline'],
                'target' => $propuesta['target'],
                'sample' => $propuesta['sample'],
                'status' => ImprovementGoal::STATUS_ACTIVE,
            ],
        );

        $this->insertLog(
            action: 'set_monthly_goal',
            form: 'MentorPage',
            description: "Objetivo del mes #{$objetivo->id}: {$objetivo->statement}",
        );

        $this->confirming = false;
        unset($this->goal, $this->proposal, $this->progress);

        $this->dispatch('show-alert', type: 'success', message: __('mentor.goal.saved'));
    }

    public function render()
    {
        return view('livewire.mentor-page');
    }

    /**
     * La frase del objetivo, congelada tal cual se le enseñó al usuario.
     *
     * @param  array<string, mixed>  $propuesta
     */
    private function statement(array $propuesta): string
    {
        return __('mentor.goal.statement', [
            'name' => $propuesta['mistake_name'],
            'baseline' => $propuesta['baseline'],
            'target' => $propuesta['target'],
        ]);
    }
}
