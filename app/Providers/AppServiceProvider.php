<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureMt5RateLimiter();

        // Los uploads temporales de Livewire van al disco local (servidor),
        // evitando el PUT directo navegador→R2 que falla por CORS.
        // El método updatedUploadedScreenshot ya mueve el fichero a R2 server-side.
        config(['livewire.temporary_file_upload.disk' => 'local']);

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

    /**
     * Freno para los endpoints del `.exe` de MetaTrader.
     *
     * Son la puerta por la que entran TODOS los datos del proyecto y estaban sin
     * `throttle`: un agente en bucle, o un `sync_token` filtrado, podían
     * martillear la API sin límite.
     *
     * Van dos topes a la vez, y el motivo del segundo no es obvio: si solo se
     * limitara por token, quien probase tokens al azar estrenaría cubo con cada
     * intento y la fuerza bruta saldría gratis. El tope por IP es el que la
     * acota. Y si solo se limitara por IP, una oficina con varios terminales
     * detrás del mismo NAT compartiría cubo sin motivo.
     *
     * Los dos límites se leen de la configuración en cada petición, así que se
     * pueden subir sin tocar código si un agente resulta ser más hablador de lo
     * previsto.
     */
    private function configureMt5RateLimiter(): void
    {
        RateLimiter::for('mt5-sync', function (Request $request) {
            $token = (string) $request->input('sync_token', '');

            $limits = [
                Limit::perMinute((int) config('services.mt5.rate_limit_ip'))->by('mt5-ip:' . $request->ip()),
            ];

            if ($token !== '') {
                $limits[] = Limit::perMinute((int) config('services.mt5.rate_limit_token'))
                    ->by('mt5-token:' . sha1($token));
            }

            return $limits;
        });
    }
}
