<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;   // <-- añade esto

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fuerza https en producción para generar URLs correctas
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        if (session()->has('locale')) {
            app()->setLocale(session()->get('locale'));
        }
    }
}
