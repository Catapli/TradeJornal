<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Actions\Strategy\RecalculateStrategyStats;
use App\Models\Account;
use App\Models\ProgramLevel;
use App\Models\ProgramObjective;
use App\Models\Strategy;
use App\Models\Trade;
use App\Models\User;
use App\Services\Sample\SampleProfile;
use App\Services\Sample\SampleTradeGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Crea y borra la cuenta de ejemplo del usuario.
 *
 * Resuelve el peor momento del producto: el panel a cero del primer día. En vez
 * de una pantalla vacía, el usuario puede llenar la aplicación con seis meses de
 * operaciones verosímiles, verla funcionando y borrarlo todo de un clic.
 *
 * Los datos van en una **cuenta aparte marcada como ejemplo**, nunca mezclados
 * con las cuentas reales: así ni contaminan sus estadísticas ni hace falta
 * distinguirlos después uno a uno.
 */
class ManageSampleAccount
{
    /** Tamaño del challenge de ejemplo. */
    private const SIZE = 50000;

    private const HISTORY_DAYS = 182;

    private const TARGET_PNL = 4020.0;

    public function __construct(
        private readonly SampleTradeGenerator $generator,
        private readonly RecalculateStrategyStats $recalculateStrategy,
    ) {}

    public function exists(User $user): bool
    {
        return Account::where('user_id', $user->id)->where('is_sample', true)->exists();
    }

    public function find(User $user): ?Account
    {
        return Account::where('user_id', $user->id)->where('is_sample', true)->first();
    }

    /**
     * Crea la cuenta de ejemplo. Si ya existe, la devuelve sin duplicar nada.
     */
    public function create(User $user): Account
    {
        if ($existing = $this->find($user)) {
            return $existing;
        }

        // Semilla ligada al usuario: sus datos de ejemplo son siempre los mismos
        // entre visitas, pero no idénticos a los de otro usuario.
        mt_srand(crc32('sample-' . $user->id));

        $level = ProgramLevel::where('size', self::SIZE)
            ->where('currency', 'USD')
            ->orderBy('id')
            ->firstOrFail();

        $objective = ProgramObjective::where('program_level_id', $level->id)
            ->orderBy('id')
            ->firstOrFail();

        $account = Account::create([
            'user_id' => $user->id,
            'program_level_id' => $level->id,
            'program_objective_id' => $objective->id,
            'name' => __('sample.account_name'),
            'type' => 'prop_firm',
            'is_sample' => true,
            'status' => 'active',
            'sync' => false,
            'platform' => 'mt5',
            'broker_name' => __('sample.broker_name'),
            'currency' => 'USD',
            'initial_balance' => self::SIZE,
            'current_balance' => self::SIZE,
            'current_equity' => self::SIZE,
            'funded_date' => now()->subDays(self::HISTORY_DAYS),
        ]);

        $strategy = Strategy::create([
            'user_id' => $user->id,
            'name' => __('sample.strategy_name'),
            'description' => __('sample.strategy_description'),
            'timeframe' => 'M5',
            'color' => '#6366f1',
            'is_main' => false,
        ]);

        [$rows, $running] = $this->generator->generate(
            $account,
            $this->generator->ensureAssets(),
            [$strategy->id],
            SampleProfile::challenge(self::HISTORY_DAYS, self::TARGET_PNL),
        );

        DB::transaction(function () use ($rows) {
            Trade::withoutEvents(function () use ($rows) {
                foreach (array_chunk($rows, 200) as $chunk) {
                    Trade::insert($chunk);
                }
            });
        });

        $balance = round(self::SIZE + $running, 2);

        $account->forceFill([
            'current_balance' => $balance,
            'current_equity' => $balance,
            'today_starting_balance' => $balance,
            'today_starting_equity' => $balance,
        ])->save();

        // Lo que haría TradeObserver, una sola vez en vez de por operación.
        CalculateAccountStatistics::clearCache($account->id);
        GenerateBalanceChartData::clearCache($account->id);
        $this->recalculateStrategy->execute($strategy);

        return $account->refresh();
    }

    /**
     * Borra la cuenta de ejemplo y todo lo que colgaba de ella.
     *
     * Las operaciones se van en cascada por la clave foránea; la estrategia de
     * ejemplo se borra aparte porque cuelga del usuario, no de la cuenta.
     */
    public function destroy(User $user): bool
    {
        $account = $this->find($user);

        if (!$account) {
            return false;
        }

        DB::transaction(function () use ($account, $user) {
            Trade::where('account_id', $account->id)->delete();

            // La estrategia de ejemplo solo se borra si nadie más la usa: si el
            // usuario le asignó operaciones suyas, se queda.
            Strategy::where('user_id', $user->id)
                ->where('name', __('sample.strategy_name'))
                ->whereDoesntHave('trades')
                ->delete();

            $account->forceDelete();
        });

        CalculateAccountStatistics::clearCache($account->id);
        GenerateBalanceChartData::clearCache($account->id);

        return true;
    }
}
