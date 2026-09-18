<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Mistakes\CountPendingReview;
use App\LogActions;
use App\Models\Trade;
use App\Support\Demo;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Repaso de errores, una operación por pantalla (Fase 6 · usabilidad).
 *
 * La cola del Laboratorio ponía las perdedoras en lista con los errores a un
 * clic, y aun así el repaso semanal se hacía cuesta arriba: para reconocer una
 * operación de hace cinco días hay que **ver el gráfico**, y eso obligaba a abrir
 * un modal, mirarlo, cerrarlo y volver a los chips. Un viaje de ida y vuelta por
 * operación, con setenta por delante.
 *
 * Aquí es al revés: el gráfico manda, ocupa la pantalla, y debajo están los
 * errores y dos botones. Se entra, se vacía la cola y se sale.
 *
 * **No es un módulo PRO**, a diferencia del Laboratorio donde vivía la cola. Esto
 * es entrada de datos, y cerrarla dejaría al usuario gratuito con un histórico
 * sin etiquetar el día que se suscriba — justo cuando el Laboratorio y el Mentor
 * tendrían que enseñarle algo. Es el mismo criterio que con el importador.
 *
 * @property-read Trade|null $trade
 * @property-read int $total
 * @property-read int $position
 */
class ReviewPage extends Component
{
    use LogActions;

    /**
     * Los ids a repasar, **congelados al entrar**.
     *
     * Etiquetar una operación la saca de la consulta de pendientes, así que si la
     * lista se recalculara en cada paso el contador iría restando por debajo y
     * «3 de 9» pasaría a «3 de 8» a mitad de repaso. Se fija una vez y se recorre.
     *
     * @var array<int, int>
     */
    public array $queue = [];

    public int $index = 0;

    /** Repasadas en esta sesión, para el mensaje del final. */
    public int $done = 0;

    public function mount(): void
    {
        $this->queue = $this->pendingIds();
    }

    #[Computed]
    public function trade(): ?Trade
    {
        $id = $this->queue[$this->index] ?? null;

        return $id === null
            ? null
            : Trade::forUserActiveAccounts()
                // `currency` va en el select: sin ella la ficha pinta el símbolo por
                // defecto sin fallar, que es la trampa clásica de esta base.
                ->with(['tradeAsset', 'account:id,name,currency', 'mistakes'])
                ->find($id);
    }

    #[Computed]
    public function total(): int
    {
        return count($this->queue);
    }

    /** La posición humana: 1 de 9, no 0 de 9. */
    #[Computed]
    public function position(): int
    {
        return min($this->index + 1, max(1, $this->total));
    }

    /** La miraste y estaba limpia: fuera de la cola, sin inventarse un error. */
    public function markClean(): void
    {
        $trade = $this->currentTrade();

        if (!$trade || $this->blockedByDemo()) {
            return;
        }

        $trade->mistakes()->sync([]);
        $this->finish($trade, 'marked_clean');
    }

    /** Lo marcado en el selector ya está guardado: aquí solo se cierra el repaso. */
    public function markReviewed(): void
    {
        $trade = $this->currentTrade();

        if (!$trade || $this->blockedByDemo()) {
            return;
        }

        $this->finish($trade, $trade->mistakes()->exists() ? 'saved' : 'marked_clean');
    }

    /** Ni limpia ni etiquetada: se deja para otro día y sigue en la cola. */
    public function skip(): void
    {
        $this->next();
    }

    public function back(): void
    {
        $this->index = max(0, $this->index - 1);
        unset($this->trade, $this->position);
        $this->announce();
    }

    /** Vuelve a preguntar por pendientes: puede que hayan entrado más. */
    public function reload(): void
    {
        $this->queue = $this->pendingIds();
        $this->index = 0;
        $this->done = 0;
        unset($this->trade, $this->total, $this->position);
        $this->announce();
    }

    public function render()
    {
        return view('livewire.review-page');
    }

    // ─────────────────────────────────────────────────────────────

    /** @return array<int, int> */
    private function pendingIds(): array
    {
        return CountPendingReview::query((int) auth()->id())
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function currentTrade(): ?Trade
    {
        return $this->trade;
    }

    private function finish(Trade $trade, string $messageKey): void
    {
        $trade->forceFill(['mistakes_reviewed_at' => now()])->save();

        CountPendingReview::forget((int) auth()->id());
        $this->done++;

        // El panel y el Laboratorio recalculan: el coste de los errores acaba de cambiar.
        $this->dispatch('mistakes-reviewed');
        $this->dispatch('show-alert', type: 'success', message: __("mistake_cost.review.{$messageKey}"));

        $this->next();
    }

    private function next(): void
    {
        $this->index++;
        unset($this->trade, $this->position);
        $this->announce();
    }

    /**
     * Le dice al visor qué operación toca ahora.
     *
     * El contenedor del gráfico lleva `wire:ignore` —si no, Livewire lo repintaría
     * y se llevaría el lienzo por delante en cada paso—, así que el cambio de
     * operación no le llega solo: hay que anunciarlo. Es el mismo evento que usa
     * el modal del detalle.
     */
    private function announce(): void
    {
        $trade = $this->trade;

        if (!$trade) {
            return;
        }

        $this->dispatch(
            'trade-selected',
            path: $trade->chart_data_path ? route('trades.chart-data', $trade->id) : null,
            entry: $trade->entry_price,
            exit: $trade->exit_price,
            direction: $trade->direction,
            mae: $trade->mae_price,
            mfe: $trade->mfe_price
        );
    }

    private function blockedByDemo(): bool
    {
        if (!Demo::active()) {
            return false;
        }

        $this->dispatch('show-alert', type: 'error', message: __('landing.demo.blocked'));

        return true;
    }
}
