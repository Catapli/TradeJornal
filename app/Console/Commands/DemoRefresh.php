<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Accounts\CalculateAccountStatistics;
use App\Actions\Accounts\GenerateBalanceChartData;
use App\Actions\Strategy\RecalculateStrategyStats;
use App\Models\Account;
use App\Models\Strategy;
use App\Support\Demo;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

/**
 * Regenera desde cero los datos de la demo pública.
 *
 * Se puede lanzar cuando se quiera: borra el usuario de demo (y en cascada sus
 * cuentas y operaciones) y lo vuelve a sembrar con la misma semilla, así que el
 * resultado es idéntico en cada ejecución.
 */
class DemoRefresh extends Command
{
    protected $signature = 'demo:refresh {--force : No pedir confirmación en producción}';

    protected $description = 'Regenera el usuario y los datos de la demo pública (/demo)';

    public function handle(): int
    {
        if (!Demo::enabled()) {
            $this->warn('La demo está desactivada (DEMO_ENABLED=false). No se ha hecho nada.');

            return self::SUCCESS;
        }

        if (app()->isProduction() && !$this->option('force')) {
            if (!$this->confirm('Esto borrará y recreará el usuario de demo en producción. ¿Continuar?')) {
                return self::FAILURE;
            }
        }

        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->recalculate();

        $this->info('Demo regenerada. Entra en ' . route('demo.enter'));

        return self::SUCCESS;
    }

    /**
     * El seeder inserta las operaciones sin eventos por rendimiento, así que aquí
     * se rehace a mano lo que normalmente haría TradeObserver: caché de cuentas y
     * estadísticas de las estrategias.
     */
    private function recalculate(): void
    {
        $user = Demo::user();

        if (!$user) {
            return;
        }

        foreach (Account::where('user_id', $user->id)->pluck('id') as $accountId) {
            CalculateAccountStatistics::clearCache($accountId);
            GenerateBalanceChartData::clearCache($accountId);
        }

        $recalculate = app(RecalculateStrategyStats::class);

        foreach (Strategy::where('user_id', $user->id)->get() as $strategy) {
            $recalculate->execute($strategy);
        }
    }
}
