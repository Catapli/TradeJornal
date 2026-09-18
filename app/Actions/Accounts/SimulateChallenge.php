<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Models\Account;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Monte Carlo sobre la distribución propia de la cuenta (Fase 6 · P3).
 *
 * ⚠️ **APARCADO EL 2026-08-28. Hoy no lo llama ninguna pantalla.**
 *
 * Se construyó con su propia página en `/challenge` y se retiró el mismo día,
 * decisión de Jordi. El cálculo no era el problema: sobre la cuenta de demo, con
 * 75 días operados, daba un 92,7 % de pasar, mediana de 10 días y fecha estimada.
 * El problema era la pantalla. Con 7 días operados —lo que tiene una cuenta real
 * recién empezada— la simulación se calla por muestra insuficiente, y lo único
 * que quedaba en pantalla eran las cuatro reglas de la fase **ya presentes en
 * `/cuentas`** vía `Account::getObjectivesProgressAttribute()`. Una pantalla
 * entera para repetir lo de al lado y avisar de que volvieras en trece días.
 *
 * Se conserva con sus tests porque el cálculo está bien y verificado. **Si se
 * retoma, que sea como una tarjeta dentro de `/cuentas`** que solo aparezca
 * cuando haya muestra, no como pantalla propia.
 *
 * La pregunta que se hace todo el que está en un challenge no es «cuánto llevo»,
 * es «¿voy a llegar?». Aquí se responde remuestreando **sus propios días**
 * (bootstrap con reemplazo) y dejando correr la fase hasta que pasa, revienta o
 * se agota el horizonte.
 *
 * Tres cosas que no se negocian, y las tres vienen de lo que costó el sistema R:
 *
 * 1. **Muestra mínima.** Por debajo de MIN_SAMPLE_DAYS días operados no sale
 *    ninguna probabilidad. Una probabilidad sacada de seis días no es una
 *    estimación, es un número inventado con aspecto de estimación, y encima uno
 *    que el usuario va a usar para decidir cuánto arriesga.
 * 2. **Solo resultado cerrado.** `BuildDailyResults` no ve el flotante, así que
 *    esto mide la pérdida *del día cerrado*, no el equity intradía. Una cuenta
 *    puede romper el límite diario en flotante y aquí no aparecería. La pantalla
 *    lo dice; nadie debe presentarlo como si vigilara el tick a tick.
 * 3. **Sin drawdown móvil.** `loss_type` está aparcado por decisión de producto,
 *    así que la pérdida total se mide contra el balance inicial, igual que en
 *    `Account::getObjectivesProgressAttribute()`. Para una cuenta con trailing,
 *    esto es optimista.
 */
class SimulateChallenge
{
    /** Días operados mínimos para que la simulación se muestre. */
    public const MIN_SAMPLE_DAYS = 20;

    /** Recorridos simulados. 2.000 deja el error de muestreo en ~1 punto. */
    public const RUNS = 2000;

    /**
     * Días operados que se simulan como máximo.
     *
     * Fijo a propósito: el esquema no guarda ninguna fecha límite de fase
     * (`accounts.end_date` es «cuándo terminó la cuenta», no «hasta cuándo
     * tienes»). Inventarse un plazo a partir de ese campo daría una cuenta atrás
     * que no existe en ningún sitio.
     */
    public const HORIZON_DAYS = 60;

    public function __construct(private readonly BuildDailyResults $daily) {}

    /**
     * @param  int|null  $seed  Semilla del generador. Solo para los tests.
     * @return array<string, mixed>
     */
    public function execute(Account $account, ?int $seed = null): array
    {
        $objective = $account->currentObjective;

        if (!$objective) {
            return ['has_objective' => false];
        }

        $days = $this->daily->execute($account);
        $initial = (float) $account->initial_balance;
        $balance = (float) $account->current_balance;
        $threshold = $initial * Account::PROFITABLE_DAY_THRESHOLD;

        $target = $objective->profit_target_percent > 0
            ? $initial * ($objective->profit_target_percent / 100)
            : 0.0;
        $dailyLimit = $objective->max_daily_loss_percent > 0
            ? $initial * ($objective->max_daily_loss_percent / 100)
            : 0.0;
        $totalLimit = $objective->max_total_loss_percent > 0
            ? $initial * ($objective->max_total_loss_percent / 100)
            : 0.0;

        $profitableDays = $days->filter(fn (array $d): bool => $d['pnl'] >= $threshold)->count();
        $minDays = (int) $objective->min_trading_days;

        $estado = [
            'has_objective' => true,
            'mode' => $target > 0 ? 'target' : 'survival',
            'sample_days' => $days->count(),
            'min_sample_days' => self::MIN_SAMPLE_DAYS,
            'has_enough_data' => $days->count() >= self::MIN_SAMPLE_DAYS,
            'profitable_days' => $profitableDays,
            'min_trading_days' => $minDays,
            'missing_profitable_days' => max(0, $minDays - $profitableDays),
            'target_left' => max(0.0, $target - ($balance - $initial)),
            'daily_limit' => $dailyLimit,
            'total_room' => $totalLimit > 0 ? max(0.0, $totalLimit - ($initial - $balance)) : 0.0,
        ];

        if (!$estado['has_enough_data']) {
            // El estado se devuelve igualmente: «cuánto falta» y «días mínimos»
            // son cuentas exactas y no dependen de la muestra. Lo único que se
            // calla son las probabilidades.
            return $estado + ['missing_sample_days' => self::MIN_SAMPLE_DAYS - $days->count()];
        }

        $muestra = $days->pluck('pnl')->values()->all();
        $ritmo = $this->daysPerWeek($days);
        $horizonte = self::HORIZON_DAYS;

        if ($seed !== null) {
            mt_srand($seed);
        }

        $resultados = ['pass' => 0, 'daily_breach' => 0, 'total_breach' => 0, 'timeout' => 0];
        $duraciones = [];
        $ultimo = count($muestra) - 1;

        for ($run = 0; $run < self::RUNS; $run++) {
            $saldo = $balance;
            $rentables = $profitableDays;
            $desenlace = 'timeout';
            $dia = 0;

            while ($dia < $horizonte) {
                $dia++;
                $pnl = $muestra[mt_rand(0, $ultimo)];

                // El límite diario se mira primero: se rompe dentro del día, antes
                // de que ese resultado llegue a contar para el acumulado de la fase.
                if ($dailyLimit > 0 && -$pnl >= $dailyLimit) {
                    $desenlace = 'daily_breach';
                    break;
                }

                $saldo += $pnl;

                if ($totalLimit > 0 && ($initial - $saldo) >= $totalLimit) {
                    $desenlace = 'total_breach';
                    break;
                }

                if ($pnl >= $threshold) {
                    $rentables++;
                }

                if ($target > 0 && ($saldo - $initial) >= $target && $rentables >= $minDays) {
                    $desenlace = 'pass';
                    $duraciones[] = $dia;
                    break;
                }
            }

            // Sin objetivo de beneficio (cuenta fondeada) pasar es sobrevivir:
            // llegar al final del horizonte sin haber roto ninguna regla dura.
            // No se apunta duración: aquí no hay «fecha en la que lo consigues»,
            // y darla sería inventarse un hito que la fase no tiene.
            if ($target <= 0 && $desenlace === 'timeout') {
                $desenlace = 'pass';
            }

            $resultados[$desenlace]++;
        }

        sort($duraciones);
        $mediana = $duraciones === [] ? null : (int) $duraciones[intdiv(count($duraciones), 2)];

        return $estado + [
            'runs' => self::RUNS,
            'pass' => $this->percent($resultados['pass']),
            'daily_breach' => $this->percent($resultados['daily_breach']),
            'total_breach' => $this->percent($resultados['total_breach']),
            'timeout' => $this->percent($resultados['timeout']),
            'median_days' => $mediana,
            'days_per_week' => $ritmo,
            'horizon_days' => $horizonte,
            'estimated_date' => $mediana === null
                ? null
                : CarbonImmutable::now()->addDays((int) ceil($mediana / $ritmo * 7))->toDateString(),
        ];
    }

    /**
     * Días operados por semana natural, para traducir «días» a fechas.
     *
     * Se mide sobre el tramo real (primer a último día operado) y no sobre la
     * antigüedad de la cuenta: una cuenta abierta en enero y estrenada en agosto
     * daría un ritmo ridículo y una fecha estimada de dentro de dos años.
     *
     * @param  Collection<int, array{date: string, pnl: float, trades: int}>  $days
     */
    private function daysPerWeek(Collection $days): float
    {
        $primero = CarbonImmutable::parse($days->first()['date']);
        $ultimo = CarbonImmutable::parse($days->last()['date']);
        $semanas = max(1.0, ($primero->diffInDays($ultimo) + 1) / 7);

        // Nadie opera más de siete días por semana, y menos de uno alarga la
        // proyección hasta lo absurdo. Los dos extremos se recortan a propósito.
        return round(max(1.0, min(7.0, $days->count() / $semanas)), 2);
    }

    private function percent(int $veces): float
    {
        return round($veces / self::RUNS * 100, 1);
    }
}
