<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;   // <-- añade esto
use App\Events\AccountSynced;           // ← AÑADE
use App\Listeners\SyncAccountListener;  // ← AÑADE
use App\Models\Account;
use App\Models\Trade;
use App\Observers\AccountObserver;
use App\Observers\TradeObserver;
use App\Services\StorageService;
use Illuminate\Support\Facades\Event;  // ← AÑADE IMPORT

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
        $this->app->singleton(StorageService::class);
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

        // 🔥 AÑADE ESTAS 2 LÍNEAS
        Event::listen(AccountSynced::class, SyncAccountListener::class);
        Trade::observe(TradeObserver::class);
        Account::observe(AccountObserver::class);
        // Trade::observe(TradeObserver::class);
    }
}
