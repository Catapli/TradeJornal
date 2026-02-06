<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Subscription extends Component
{

    // 1. Defínelas como strings vacías o nulas al inicio
    public string $monthlyPriceId = '';
    public string $yearlyPriceId = '';

    // 2. Asígnales valor en el método mount()
    public function mount()
    {
        $this->monthlyPriceId = env("STRIPE_PRICE_MONTHLY");
        $this->yearlyPriceId = env("STRIPE_PRICE_YEARLY");
    }

    public function subscribe($period)
    {
        $priceId = $period === 'yearly' ? $this->yearlyPriceId : $this->monthlyPriceId;

        // 1. Crea el Checkout pero NO lo retornes directo
        $checkout = Auth::user()
            ->newSubscription('default', $priceId)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('checkout.success'),
                'cancel_url' => route('checkout.cancel'),
            ]);

        // 2. Extrae la URL de Stripe (que es lo que nos importa)
        // Cashier devuelve un objeto Checkout, y este tiene acceso a la URL
        return redirect($checkout->url);
    }

    public function render()
    {
        return view('livewire.settings.subscription');
    }
}
