<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Accounts\ManageSampleAccount;
use App\LogActions;
use App\Models\Trade;
use App\Support\Demo;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Throwable;

/**
 * Tarjeta de datos de ejemplo.
 *
 * Tiene dos caras y ninguna se ve cuando no hace falta:
 *  - Al usuario **sin ninguna operación** le ofrece llenar la aplicación con una
 *    cuenta de ejemplo, que es la respuesta al panel a cero del primer día.
 *  - Al que **ya la tiene** le recuerda que está mirando datos inventados y le
 *    da el botón para borrarlos.
 */
class SampleDataCard extends Component
{
    use LogActions;

    public bool $hasSample = false;

    public bool $hasTrades = false;

    public function mount(ManageSampleAccount $sample): void
    {
        $this->refreshState($sample);
    }

    public function create(ManageSampleAccount $sample): void
    {
        // En la demo todo es de ejemplo ya, y además DemoGuard bloquea la escritura.
        if (Demo::active()) {
            return;
        }

        try {
            $sample->create(Auth::user());
        } catch (Throwable $e) {
            $this->logError($e, 'create', 'SampleDataCard', 'Fallo creando la cuenta de ejemplo');
            $this->dispatch('show-alert', ['type' => 'error', 'message' => __('sample.error')]);

            return;
        }

        $this->refreshState($sample);
        $this->insertLog(action: 'Cuenta de ejemplo creada', form: 'SampleDataCard');

        $this->dispatch('show-alert', ['type' => 'success', 'message' => __('sample.created')]);
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function destroySample(ManageSampleAccount $sample): void
    {
        if (Demo::active()) {
            return;
        }

        $sample->destroy(Auth::user());
        $this->refreshState($sample);

        $this->insertLog(action: 'Cuenta de ejemplo eliminada', form: 'SampleDataCard');
        $this->dispatch('show-alert', ['type' => 'success', 'message' => __('sample.deleted')]);
        $this->redirectRoute('dashboard', navigate: true);
    }

    private function refreshState(ManageSampleAccount $sample): void
    {
        $user = Auth::user();

        $this->hasSample = $sample->exists($user);

        // «Tiene operaciones» significa operaciones suyas, no las de ejemplo.
        $this->hasTrades = Trade::whereHas(
            'account',
            fn ($query) => $query->where('user_id', $user->id)->where('is_sample', false)
        )->exists();
    }

    public function render()
    {
        return view('livewire.sample-data-card');
    }
}
