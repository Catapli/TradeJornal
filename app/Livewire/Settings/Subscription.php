<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Support\Demo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Subscription extends Component
{
    public string $monthlyPriceId = '';

    public string $yearlyPriceId = '';

    public function mount(): void
    {
        // config() en lugar de env(): con la configuración cacheada en producción
        // env() devuelve null y el checkout se creaba con un price vacío.
        $this->monthlyPriceId = (string) config('services.stripe.monthly');
        $this->yearlyPriceId = (string) config('services.stripe.yearly');
    }

    public function subscribe(string $period): ?RedirectResponse
    {
        // En la demo no se abre un checkout real: crearía un cliente en Stripe
        // a nombre del usuario compartido de demostración.
        if (Demo::active()) {
            $this->dispatch('notify', __('landing.demo.blocked'));

            return null;
        }

        $priceId = $period === 'yearly' ? $this->yearlyPriceId : $this->monthlyPriceId;

        if ($priceId === '') {
            $this->dispatch('notify', __('landing.pricing.unavailable'));

            return null;
        }

        $checkout = Auth::user()
            ->newSubscription('default', $priceId)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('checkout.success'),
                'cancel_url' => route('checkout.cancel'),
            ]);

        return redirect($checkout->url);
    }

    public function render()
    {
        return view('livewire.settings.subscription');
    }
}
