<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Mistake;
use App\Models\ProgramLevel;
use App\Models\ProgramObjective;
use App\Models\Role;
use App\Models\SessionNote;
use App\Models\Strategy;
use App\Models\Trade;
use App\Models\TradingObjective;
use App\Models\TradingPlan;
use App\Models\TradingSession;
use App\Models\User;
use App\Services\Sample\SampleChartGenerator;
use App\Services\Sample\SampleProfile;
use App\Services\Sample\SampleTradeGenerator;
use App\Services\StorageService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos de la demo pública (/demo).
 *
 * Genera un usuario de solo lectura con seis meses de historial creíble: un
 * challenge en curso, una cuenta fondeada y una quemada, con diario escrito,
 * sesiones cerradas, errores etiquetados y algunas auditorías de IA ya hechas.
 *
 * Es **determinista**: la semilla del generador es fija, así que dos ejecuciones
 * producen exactamente los mismos datos. Eso permite que las capturas de la
 * landing y lo que ve el visitante coincidan.
 *
 * Idempotente: borra y vuelve a crear el usuario de demo entero. Se relanza con
 * `php artisan demo:refresh`.
 *
 * Requisitos previos: PropFirmsSeeder y MistakesSeeder.
 */
class DemoSeeder extends Seeder
{
    /** Semilla fija: mismos datos en cada ejecución. */
    private const SEED = 20260826;

    /** Días de historial hacia atrás desde hoy. */
    private const HISTORY_DAYS = 182;

    /**
     * Operaciones de la demo que llevan velas.
     *
     * No todas: cada payload son ~600 velas subidas a R2 una por una, y con 324
     * operaciones la siembra tardaría minutos para enseñar lo mismo. Las más
     * recientes son las que abre el visitante.
     */
    private const CHART_TRADES = 40;

    private CarbonImmutable $today;

    private SampleTradeGenerator $generator;

    public function run(): void
    {
        mt_srand(self::SEED);
        $this->today = CarbonImmutable::today();
        $this->generator = app(SampleTradeGenerator::class);

        $this->command?->info('Regenerando datos de la demo…');

        $user = $this->resetDemoUser();
        $this->grantProAccess($user);

        $assets = $this->ensureAssets();
        $strategies = $this->createStrategies($user);
        $this->createMasterRules($user);

        $accounts = $this->createAccounts($user);

        $allTrades = collect();
        foreach ($accounts as $key => $account) {
            $allTrades = $allTrades->merge(
                $this->createTradesFor($account, $assets, $strategies, $key)
            );
        }

        $this->attachMistakes($allTrades);
        $this->addAiAnalysis($allTrades);
        $this->addSampleCharts($user, $allTrades);
        $this->createJournal($user, $allTrades);
        $this->createSessions($user, $accounts['challenge'], $strategies, $allTrades);

        $this->command?->info(sprintf(
            'Demo lista: %d cuentas, %d operaciones. Entra en /demo.',
            count($accounts),
            $allTrades->count()
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // Usuario
    // ─────────────────────────────────────────────────────────────

    private function resetDemoUser(): User
    {
        $email = (string) config('demo.email');

        // Los ficheros de R2 no se van en cascada: si no se borran aquí, cada
        // `demo:refresh` deja atrás las velas del usuario de demo anterior.
        $anterior = User::where('email', $email)->first();

        if ($anterior) {
            app(StorageService::class)->deleteUserDirectory($anterior->id);
        }

        // El borrado en cascada de accounts → trades limpia el histórico anterior.
        User::where('email', $email)->delete();

        $roleId = Role::firstOrCreate(['name' => 'user'], ['label' => 'Usuario'])->id;

        return User::create([
            'name' => (string) config('demo.name'),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(40)),
            'role_id' => $roleId,
            'is_superadmin' => false,
            'sync_token' => Str::random(32),
        ]);
    }

    /**
     * Suscripción ficticia para que la demo enseñe el producto completo.
     *
     * No toca Stripe: Cashier decide con `stripe_status`, así que una fila local
     * con estado `active` basta para que `subscribed('default')` sea true.
     */
    private function grantProAccess(User $user): void
    {
        DB::table('subscriptions')->insert([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'demo_' . Str::random(20),
            'stripe_status' => 'active',
            'stripe_price' => 'demo_price_monthly',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createMasterRules(User $user): void
    {
        $rules = [
            'No abrir ninguna operación sin stop definido.',
            'Máximo 3 operaciones al día.',
            'Parar la sesión tras dos pérdidas seguidas.',
            'No operar en los 15 minutos previos a una noticia de alto impacto.',
            'Nunca mover el stop en contra.',
        ];

        foreach ($rules as $text) {
            TradingObjective::create([
                'user_id' => $user->id,
                'text' => $text,
                'is_active' => true,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Catálogos
    // ─────────────────────────────────────────────────────────────

    /** @return array<string, int> símbolo => trade_asset_id */
    private function ensureAssets(): array
    {
        return $this->generator->ensureAssets();
    }

    /** @return array<string, Strategy> */
    private function createStrategies(User $user): array
    {
        $definitions = [
            'orb' => [
                'name' => 'Opening Range Breakout',
                'timeframe' => 'M5',
                'color' => '#6366f1',
                'is_main' => true,
                'description' => 'Rotura del rango de los primeros 15 minutos de la apertura de Nueva York, a favor del sesgo diario.',
                'rules' => [
                    'Esperar al cierre de la vela de rotura de M5.',
                    'Solo a favor de la EMA 50 en M15.',
                    'Stop al otro lado del rango, objetivo a 2R.',
                    'No operar si el rango es mayor que la media de 10 días.',
                ],
            ],
            'poi' => [
                'name' => 'POI / Trampas',
                'timeframe' => 'M15',
                'color' => '#10b981',
                'is_main' => false,
                'description' => 'Order blocks marcados en H4, diferenciando punto de interés real de trampa de liquidez.',
                'rules' => [
                    'Marcar los POI antes de la apertura, nunca en caliente.',
                    'Exigir barrido de liquidez previo.',
                    'Entrada solo con confirmación en M5.',
                ],
            ],
            'news' => [
                'name' => 'Continuación post-noticia',
                'timeframe' => 'M5',
                'color' => '#f59e0b',
                'is_main' => false,
                'description' => 'Entrada 20 minutos después del dato, cuando la volatilidad se ha asentado y hay dirección clara.',
                'rules' => [
                    'Nunca entrar antes del dato.',
                    'Esperar 20 minutos completos.',
                    'Riesgo reducido a la mitad.',
                ],
            ],
        ];

        $strategies = [];
        foreach ($definitions as $key => $data) {
            $strategies[$key] = Strategy::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'timeframe' => $data['timeframe'],
                'color' => $data['color'],
                'is_main' => $data['is_main'],
                'rules' => json_encode($data['rules'], JSON_UNESCAPED_UNICODE),
            ]);
        }

        return $strategies;
    }

    // ─────────────────────────────────────────────────────────────
    // Cuentas
    // ─────────────────────────────────────────────────────────────

    /** @return array<string, Account> */
    private function createAccounts(User $user): array
    {
        $accounts = [];

        $accounts['challenge'] = $this->makeAccount($user, [
            'name' => 'Challenge 50k · Fase 1',
            'size' => 50000,
            'phase' => 1,
            'status' => 'active',
            'type' => 'prop_firm',
            'funded_date' => $this->today->subDays(self::HISTORY_DAYS),
        ]);

        $accounts['funded'] = $this->makeAccount($user, [
            'name' => 'Fondeada 25k',
            'size' => 25000,
            'phase' => 0,
            'status' => 'active',
            'type' => 'prop_firm',
            'funded_date' => $this->today->subDays(120),
        ]);

        $accounts['burned'] = $this->makeAccount($user, [
            'name' => 'Challenge 10k (quemada)',
            'size' => 10000,
            'phase' => 1,
            'status' => 'burned',
            'type' => 'prop_firm',
            'funded_date' => $this->today->subDays(self::HISTORY_DAYS),
            'end_date' => $this->today->subDays(96),
        ]);

        // Plan de trading solo en la cuenta principal: es la que se enseña.
        TradingPlan::create([
            'account_id' => $accounts['challenge']->id,
            'max_daily_trades' => 3,
            'max_daily_loss_percent' => 2.0,
            'daily_profit_target_percent' => 1.5,
            'start_time' => '09:00',
            'end_time' => '17:30',
            'is_active' => true,
        ]);

        return $accounts;
    }

    /** @param array<string, mixed> $data */
    private function makeAccount(User $user, array $data): Account
    {
        $level = ProgramLevel::where('size', $data['size'])
            ->where('currency', 'USD')
            ->orderBy('id')
            ->firstOrFail();

        $objective = ProgramObjective::where('program_level_id', $level->id)
            ->where('phase_number', (string) $data['phase'])
            ->orderBy('id')
            ->first()
            ?? ProgramObjective::where('program_level_id', $level->id)->orderBy('id')->firstOrFail();

        return Account::create([
            'user_id' => $user->id,
            'program_level_id' => $level->id,
            'program_objective_id' => $objective->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'status' => $data['status'],
            'sync' => false,
            'platform' => 'mt5',
            'broker_name' => $level->program?->propFirm?->name ?? 'Prop Firm',
            'currency' => 'USD',
            'initial_balance' => $data['size'],
            'current_balance' => $data['size'],
            'current_equity' => $data['size'],
            'today_starting_balance' => $data['size'],
            'today_starting_equity' => $data['size'],
            'funded_date' => $data['funded_date'],
            'end_date' => $data['end_date'] ?? null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Operaciones
    // ─────────────────────────────────────────────────────────────

    /**
     * Perfil de cada cuenta. La quemada tiene peor acierto y esperanza negativa:
     * la demo también tiene que enseñar cómo se ve una cuenta que se va al garete.
     *
     * @param  array<string, int>  $assets
     * @param  array<string, Strategy>  $strategies
     * @return \Illuminate\Support\Collection<int, Trade>
     */
    private function createTradesFor(Account $account, array $assets, array $strategies, string $profileKey): \Illuminate\Support\Collection
    {
        $profile = match ($profileKey) {
            'challenge' => SampleProfile::challenge(self::HISTORY_DAYS, 4020.0),
            'funded' => SampleProfile::funded(120, 1840.0),
            'burned' => SampleProfile::burned(self::HISTORY_DAYS, 96, -1080.0),
        };

        $strategyIds = array_map(static fn (Strategy $s) => $s->id, array_values($strategies));

        [$rows, $running] = $this->generator->generate($account, $assets, $strategyIds, $profile, $this->today);

        // Sin eventos: el observador solo invalida caché y encola recálculos, y
        // aquí se hacen en bloque al final. Con cientos de operaciones ahorra minutos.
        Trade::withoutEvents(function () use ($rows) {
            foreach (array_chunk($rows, 200) as $chunk) {
                Trade::insert($chunk);
            }
        });

        $finalBalance = round((float) $account->initial_balance + $running, 2);

        $account->update([
            'current_balance' => $finalBalance,
            'current_equity' => $finalBalance,
            'today_starting_balance' => $finalBalance,
            'today_starting_equity' => $finalBalance,
        ]);

        return Trade::where('account_id', $account->id)->get();
    }

    // ─────────────────────────────────────────────────────────────
    // Errores, IA, diario y sesiones
    // ─────────────────────────────────────────────────────────────

    /** @param \Illuminate\Support\Collection<int, Trade> $trades */
    private function attachMistakes(\Illuminate\Support\Collection $trades): void
    {
        $mistakes = Mistake::whereNull('user_id')->get()->keyBy('slug');
        if ($mistakes->isEmpty()) {
            return;
        }

        $rows = [];
        foreach ($trades as $trade) {
            // Los errores se concentran donde de verdad aparecen: en las pérdidas.
            $chance = $trade->pnl < 0 ? 45 : 12;
            if (mt_rand(1, 100) > $chance) {
                continue;
            }

            $pool = $trade->pnl < 0
                ? ['revenge_trading', 'moved_stop_loss', 'averaging_down', 'fomo', 'overtrading', 'no_setup', 'counter_trend', 'held_loser', 'excessive_risk']
                : ['early_exit', 'late_entry', 'wrong_size'];

            $slug = $this->pick($pool);
            if (!isset($mistakes[$slug])) {
                continue;
            }

            $rows[] = [
                'trade_id' => $trade->id,
                'mistake_id' => $mistakes[$slug]->id,
                'created_at' => $trade->entry_time,
                'updated_at' => $trade->entry_time,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('trade_mistake')->insert($chunk);
        }
    }

    /**
     * Auditorías ya escritas en las operaciones más recientes.
     *
     * En la demo el auditor real está desactivado (no se gastan llamadas de pago),
     * así que estas son las que ve el visitante al abrir una operación.
     *
     * @param  \Illuminate\Support\Collection<int, Trade>  $trades
     */
    private function addAiAnalysis(\Illuminate\Support\Collection $trades): void
    {
        $samples = [
            "**🎯 Calidad de Entrada:** Regular. La entrada se produce en el 74 % del rango de las últimas 50 velas, con poco recorrido hasta el extremo opuesto.\n\n**🧠 Gestión (Miedo/Codicia):** El MAE muestra que soportaste una excursión adversa considerable antes de que el precio girara, y cerraste bastante por debajo del MFE alcanzado. Salida por miedo.\n\n**⚖️ Veredicto Final:** Ejecución amateur: entrada perseguida y cierre prematuro.\n\n**💡 Consejo de Mejora:** Define el objetivo antes de entrar y no lo muevas mientras la estructura aguante.\n\n**🏆 Nota de Ejecución:** 4/10",
            "**🎯 Calidad de Entrada:** Excelente. Entrada en el 18 % del rango, a favor de la EMA y con recorrido amplio hasta el extremo contrario.\n\n**🧠 Gestión (Miedo/Codicia):** MAE muy contenido y salida cerca del MFE: dejaste correr la operación sin interferir.\n\n**⚖️ Veredicto Final:** Ejecución profesional. Este es el patrón que conviene repetir.\n\n**💡 Consejo de Mejora:** Documenta las condiciones exactas de esta entrada en el playbook.\n\n**🏆 Nota de Ejecución:** 9/10",
            "**🎯 Calidad de Entrada:** Mala. El ratio de la vela de entrada indica que entraste sobre un impulso ya extendido, señal clásica de FOMO.\n\n**🧠 Gestión (Miedo/Codicia):** El MFE apenas superó el precio de entrada: la operación nunca llegó a estar cómoda.\n\n**⚖️ Veredicto Final:** Entrada impulsiva sin setup. El resultado es secundario, el proceso está roto.\n\n**💡 Consejo de Mejora:** Impón una espera obligatoria de una vela completa antes de ejecutar.\n\n**🏆 Nota de Ejecución:** 2/10",
            "**🎯 Calidad de Entrada:** Regular. Zona correcta, momento discutible: entraste antes de la confirmación que exige tu propia estrategia.\n\n**🧠 Gestión (Miedo/Codicia):** Aguantaste el drawdown sin mover el stop, lo cual es correcto, pero la duración triplica tu media.\n\n**⚖️ Veredicto Final:** Disciplina de gestión buena, disciplina de entrada mejorable.\n\n**💡 Consejo de Mejora:** Añade la confirmación a tu checklist de sesión y no ejecutes sin marcarla.\n\n**🏆 Nota de Ejecución:** 6/10",
        ];

        $targets = $trades->sortByDesc('entry_time')->take(12);

        foreach ($targets as $i => $trade) {
            Trade::withoutEvents(fn () => Trade::whereKey($trade->id)->update([
                'ai_analysis' => $samples[$i % count($samples)],
            ]));
        }
    }

    /**
     * Velas de ejemplo para que el reproductor barra a barra se vea en la demo.
     *
     * Las velas reales solo llegan por el agente de MetaTrader, así que sin esto
     * el visitante no vería nunca el bloque más vistoso de la Fase 6. Lo que se
     * sube **no es mercado**: es un camino inventado que pasa por la entrada, la
     * salida, el MAE y el MFE que ya guarda la operación (`SampleChartGenerator`).
     *
     * @param  \Illuminate\Support\Collection<int, Trade>  $trades
     */
    private function addSampleCharts(User $user, \Illuminate\Support\Collection $trades): void
    {
        $storage = app(StorageService::class);
        $generator = app(SampleChartGenerator::class);

        // `$trades` es una colección normal (viene de un merge), no de Eloquent:
        // el activo se recarga aquí en una sola consulta.
        $objetivo = Trade::with('tradeAsset')
            ->whereIn('id', $trades->sortByDesc('entry_time')->take(self::CHART_TRADES)->pluck('id'))
            ->get();

        foreach ($objetivo as $trade) {
            $path = $storage->tradeChartPath($user->id, (string) $trade->ticket);

            $storage->putJson($path, $generator->generate(
                $trade,
                $trade->tradeAsset?->symbol ?? 'EURUSD',
            ));

            Trade::withoutEvents(fn () => Trade::whereKey($trade->id)->update([
                'chart_data_path' => $path,
            ]));
        }

        $this->command?->info(sprintf('  Velas de ejemplo en %d operaciones.', $objetivo->count()));
    }

    /** @param \Illuminate\Support\Collection<int, Trade> $trades */
    private function createJournal(User $user, \Illuminate\Support\Collection $trades): void
    {
        $pnlByDay = $trades->groupBy(fn (Trade $t) => CarbonImmutable::parse($t->entry_time)->toDateString())
            ->map(fn ($group) => $group->sum('pnl'));

        $moods = ['confident', 'neutral', 'anxious', 'frustrated', 'focused'];

        for ($offset = 75; $offset >= 0; $offset--) {
            $day = $this->today->subDays($offset);

            if ($day->isWeekend() || mt_rand(1, 100) > 72) {
                continue;
            }

            $pnl = (float) ($pnlByDay[$day->toDateString()] ?? 0);
            $good = $pnl >= 0;

            JournalEntry::create([
                'user_id' => $user->id,
                'date' => $day->toDateString(),
                'mood' => $good ? $this->pick(['confident', 'focused', 'neutral']) : $this->pick(['frustrated', 'anxious', 'neutral']),
                'pre_market_mood' => $this->pick($moods),
                'pre_market_notes' => $this->pick([
                    'Sesión europea tranquila. Espero rango hasta la apertura americana.',
                    'Hoy hay dato de empleo a las 14:30: nada abierto antes.',
                    'Vengo de dos días buenos, ojo con confiarme y subir el tamaño.',
                    'Dormí mal. Media posición como máximo.',
                    'Plan claro: solo el setup principal y máximo dos intentos.',
                ]),
                'daily_objectives' => [
                    ['done' => mt_rand(0, 1) === 1, 'text' => 'Máximo 3 operaciones.'],
                    ['done' => mt_rand(0, 1) === 1, 'text' => 'No operar noticias.'],
                    ['done' => $good, 'text' => 'Respetar el stop inicial.'],
                ],
                'content' => $good
                    ? $this->pick([
                        'Día correcto. Entré donde tocaba y dejé correr la primera operación hasta el objetivo.',
                        'Poca actividad pero de calidad. Me quedé fuera de dos setups dudosos y fue lo mejor del día.',
                        'Cumplí el plan entero. El resultado casi da igual: el proceso fue limpio.',
                    ])
                    : $this->pick([
                        'Mal día. Después de la primera pérdida entré otra vez sin setup y lo pagué.',
                        'Rompí mi regla de parar tras dos pérdidas. Ahí está todo el daño.',
                        'El mercado no daba nada y me lo inventé. Error mío, no del mercado.',
                    ]),
                'plan_for_tomorrow' => $this->pick([
                    'Revisar el playbook antes de abrir. Solo setup principal.',
                    'Empezar con media posición hasta encadenar dos operaciones limpias.',
                    'No tocar nada hasta la apertura americana.',
                ]),
                'discipline_score' => $good ? mt_rand(70, 98) : mt_rand(28, 65),
                'created_at' => $day->setTime(8, 30),
                'updated_at' => $day->setTime(22, 0),
            ]);
        }
    }

    /**
     * @param  array<string, Strategy>  $strategies
     * @param  \Illuminate\Support\Collection<int, Trade>  $trades
     */
    private function createSessions(User $user, Account $account, array $strategies, \Illuminate\Support\Collection $trades): void
    {
        $strategyIds = array_map(fn (Strategy $s) => $s->id, array_values($strategies));
        $accountTrades = $trades->where('account_id', $account->id);

        for ($offset = 45; $offset >= 1; $offset--) {
            $day = $this->today->subDays($offset);

            if ($day->isWeekend() || mt_rand(1, 100) > 85) {
                continue;
            }

            $dayTrades = $accountTrades->filter(
                fn (Trade $t) => CarbonImmutable::parse($t->entry_time)->isSameDay($day)
            );

            if ($dayTrades->isEmpty()) {
                continue;
            }

            $pnl = round((float) $dayTrades->sum('pnl'), 2);
            $startBalance = round((float) $account->initial_balance + mt_rand(-500, 4000), 2);

            $session = TradingSession::create([
                'user_id' => $user->id,
                'account_id' => $account->id,
                'strategy_id' => $strategyIds[array_rand($strategyIds)],
                'start_time' => $day->setTime(8, 45),
                'end_time' => $day->setTime(17, mt_rand(0, 59)),
                'start_balance' => $startBalance,
                'end_balance' => round($startBalance + $pnl, 2),
                'start_mood' => $this->pick(['confident', 'neutral', 'anxious', 'focused']),
                'end_mood' => $pnl >= 0 ? $this->pick(['confident', 'focused']) : $this->pick(['frustrated', 'anxious']),
                'pre_session_notes' => $this->pick([
                    'Rango asiático estrecho, espero rotura en la apertura de Londres.',
                    'Sin noticias relevantes. Solo setup principal.',
                    'Vengo tocado del día de ayer: media posición.',
                ]),
                'post_session_notes' => $pnl >= 0
                    ? 'Sesión ordenada. Cumplí el checklist entero.'
                    : 'Me salté el checklist en la segunda operación y ahí se fue el día.',
                'total_trades' => $dayTrades->count(),
                'session_pnl' => $pnl,
                'session_pnl_percent' => round($pnl / max($startBalance, 1) * 100, 2),
                'checklist_state' => [
                    'Plan revisado' => true,
                    'Riesgo definido' => true,
                    'Sin noticias en 30 min' => mt_rand(0, 1) === 1,
                    'Máximo 3 operaciones' => $dayTrades->count() <= 3,
                ],
                'status' => 'closed',
                'created_at' => $day->setTime(8, 45),
                'updated_at' => $day->setTime(17, 30),
            ]);

            foreach (range(1, mt_rand(1, 3)) as $n) {
                $at = $day->setTime(mt_rand(9, 16), mt_rand(0, 59));
                SessionNote::create([
                    'trading_session_id' => $session->id,
                    'note' => $this->pick([
                        'El precio se acerca al POI de H4, preparado pero sin entrar todavía.',
                        'Estoy notando prisa por recuperar. Aviso a mí mismo: no.',
                        'Primera operación cerrada en objetivo. Sensación buena.',
                        'Segundo intento fallido. Según mi regla, se acabó la sesión.',
                        'Rango sin dirección. Mejor no forzarlo.',
                    ]),
                    'mood' => $this->pick(['confident', 'neutral', 'anxious', 'frustrated']),
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────

    /** @param array<int, string> $options */
    private function pick(array $options): string
    {
        return $options[array_rand($options)];
    }
}
