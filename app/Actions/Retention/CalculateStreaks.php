<?php

declare(strict_types=1);

namespace App\Actions\Retention;

use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rachas de disciplina.
 *
 * Tres números, y cada uno significa exactamente lo que dice su explicación —
 * que es la única forma de que una racha motive en vez de irritar:
 *
 *  · **Diario**: días laborables seguidos con algo escrito en el diario. El fin
 *    de semana no cuenta ni rompe: nadie opera el domingo y castigarlo haría que
 *    ninguna racha pasara de cinco.
 *  · **Sin errores graves**: días *operados* seguidos sin ninguna operación
 *    marcada con un error de peso 3. Los días sin operar no suman ni rompen,
 *    porque no arriesgar no es disciplina.
 *  · **Semanas con el plan cumplido**: semanas cerradas en las que todos los
 *    objetivos del día quedaron marcados y no hubo ningún error grave.
 *
 * Las cuentas de ejemplo quedan fuera: sus operaciones las inventó la aplicación
 * y regalarían una racha que el usuario no ha hecho.
 */
final class CalculateStreaks
{
    /** Peso a partir del cual un error se considera grave (mistakes.weight). */
    public const SEVERE_WEIGHT = 3;

    /** Hasta dónde miramos atrás. Más allá de esto ya no es una racha, es un año. */
    private const MAX_DAYS = 366;

    public function execute(User $user, bool $forceRefresh = false): array
    {
        $key = self::cacheKey($user->id);

        if ($forceRefresh) {
            Cache::forget($key);
        }

        // Cinco minutos, como el resto de KPIs cacheados del proyecto. La racha
        // solo cambia al escribir el diario o al etiquetar un error, y ambos
        // sitios llaman a forget().
        return Cache::remember($key, now()->addMinutes(5), fn (): array => $this->compute($user));
    }

    public static function cacheKey(int $userId): string
    {
        return "streaks_{$userId}";
    }

    /** Invalidación explícita: diario, ritual y etiquetado de errores. */
    public static function forget(int $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    private function compute(User $user): array
    {
        $today = $user->nowInTimezone()->startOfDay();
        $from = $today->subDays(self::MAX_DAYS);

        $journalDays = $this->journalDays($user->id, $from);
        $tradedDays = $this->tradedDays($user->id, $from);
        $severeDays = $this->severeMistakeDays($user->id, $from);
        $brokenObjectiveDays = $this->brokenObjectiveDays($user->id, $from);

        return [
            'journal' => $this->journalStreak($today, $journalDays),
            'clean' => $this->cleanStreak($today, $tradedDays, $severeDays),
            'plan' => $this->planStreak($journalDays, $tradedDays, $severeDays, $brokenObjectiveDays, $today),
            'calendar' => $this->calendar($today, $journalDays, $tradedDays, $severeDays),
        ];
    }

    // ── Racha de diario ──────────────────────────────────────────

    /**
     * Días laborables seguidos con diario, contando hacia atrás.
     *
     * Si hoy todavía no está escrito la racha no se rompe: sigue viva hasta el
     * final del día. Por eso, cuando hoy está en blanco, se empieza a contar
     * desde el día laborable anterior.
     */
    private function journalStreak(CarbonImmutable $today, array $journalDays): array
    {
        $writtenToday = isset($journalDays[$today->toDateString()]);

        $streak = $writtenToday ? 1 : 0;
        $cursor = $this->previousWeekday($today);

        while ($streak < self::MAX_DAYS && isset($journalDays[$cursor->toDateString()])) {
            $streak++;
            $cursor = $this->previousWeekday($cursor);
        }

        return [
            'current' => $streak,
            'today' => $writtenToday,
        ];
    }

    // ── Racha sin errores graves ─────────────────────────────────

    /** Días operados seguidos, del último hacia atrás, sin ningún error de peso 3. */
    private function cleanStreak(CarbonImmutable $today, array $tradedDays, array $severeDays): array
    {
        $streak = 0;
        $brokenOn = null;

        foreach (array_keys($tradedDays) as $day) {
            if (isset($severeDays[$day])) {
                $brokenOn = $day;
                break;
            }

            $streak++;
        }

        return [
            'current' => $streak,
            'broken_on' => $brokenOn,
            'traded_today' => isset($tradedDays[$today->toDateString()]),
        ];
    }

    // ── Semanas con el plan cumplido ─────────────────────────────

    /**
     * Semanas seguidas sin objetivos pendientes ni errores graves.
     *
     * La semana en curso no cuenta hasta que termina: todavía puede romperse.
     * Una semana sin diario ni operaciones tampoco cuenta — no hay nada que
     * demuestre que se cumplió el plan, y regalar semanas en blanco convertiría
     * la racha en un premio por no trabajar.
     */
    private function planStreak(
        array $journalDays,
        array $tradedDays,
        array $severeDays,
        array $brokenObjectiveDays,
        CarbonImmutable $today
    ): array {
        $week = $today->startOfWeek()->subWeek();
        $streak = 0;

        for ($i = 0; $i < 52; $i++) {
            $days = [];
            for ($d = 0; $d < 7; $d++) {
                $days[] = $week->addDays($d)->toDateString();
            }

            $hasActivity = $this->anyDay($days, $journalDays) || $this->anyDay($days, $tradedDays);

            if (!$hasActivity) {
                break;
            }

            if ($this->anyDay($days, $severeDays) || $this->anyDay($days, $brokenObjectiveDays)) {
                break;
            }

            $streak++;
            $week = $week->subWeek();
        }

        return ['current' => $streak];
    }

    // ── Calendario del mes en curso ──────────────────────────────

    /** Un punto por día transcurrido del mes, para la tarjeta del panel. */
    private function calendar(CarbonImmutable $today, array $journalDays, array $tradedDays, array $severeDays): array
    {
        $days = [];
        $cursor = $today->startOfMonth();

        while ($cursor->month === $today->month && $cursor->lte($today)) {
            $key = $cursor->toDateString();

            $days[$key] = [
                'day' => $cursor->day,
                'journal' => isset($journalDays[$key]),
                'traded' => isset($tradedDays[$key]),
                'severe' => isset($severeDays[$key]),
                'weekend' => $cursor->isWeekend(),
            ];

            $cursor = $cursor->addDay();
        }

        return $days;
    }

    // ── Consultas ────────────────────────────────────────────────

    /** @return array<string, true> días con algo escrito, del más reciente al más antiguo */
    private function journalDays(int $userId, CarbonImmutable $from): array
    {
        return JournalEntry::query()
            ->where('user_id', $userId)
            ->where('date', '>=', $from->toDateString())
            ->where(function ($q) {
                // Una entrada existe en cuanto se abre el día en el diario, así
                // que «escrito» exige contenido de verdad en alguno de sus campos.
                $q->whereRaw("COALESCE(TRIM(content), '') <> ''")
                    ->orWhereRaw("COALESCE(TRIM(pre_market_notes), '') <> ''")
                    ->orWhereNotNull('pre_market_mood');
            })
            ->orderByDesc('date')
            ->pluck('date')
            ->mapWithKeys(fn ($date): array => [CarbonImmutable::parse($date)->toDateString() => true])
            ->all();
    }

    /** @return array<string, true> días con alguna operación cerrada */
    private function tradedDays(int $userId, CarbonImmutable $from): array
    {
        return $this->tradeDayQuery($userId, $from)
            ->orderByDesc('day')
            ->pluck('day')
            ->mapWithKeys(fn ($day): array => [CarbonImmutable::parse($day)->toDateString() => true])
            ->all();
    }

    /** @return array<string, true> días con alguna operación marcada con un error grave */
    private function severeMistakeDays(int $userId, CarbonImmutable $from): array
    {
        return $this->tradeDayQuery($userId, $from)
            ->join('trade_mistake', 'trade_mistake.trade_id', '=', 'trades.id')
            ->join('mistakes', 'mistakes.id', '=', 'trade_mistake.mistake_id')
            ->where('mistakes.weight', '>=', self::SEVERE_WEIGHT)
            ->pluck('day')
            ->mapWithKeys(fn ($day): array => [CarbonImmutable::parse($day)->toDateString() => true])
            ->all();
    }

    /** Días con algún objetivo del plan sin marcar. */
    private function brokenObjectiveDays(int $userId, CarbonImmutable $from): array
    {
        return JournalEntry::query()
            ->where('user_id', $userId)
            ->where('date', '>=', $from->toDateString())
            ->whereNotNull('daily_objectives')
            ->get(['date', 'daily_objectives'])
            ->filter(fn (JournalEntry $entry): bool => collect($entry->daily_objectives ?? [])
                ->contains(fn ($objective): bool => empty($objective['done'])))
            ->mapWithKeys(fn (JournalEntry $entry): array => [$entry->date->toDateString() => true])
            ->all();
    }

    /** Base común: operaciones reales del usuario agrupadas por día de cierre. */
    private function tradeDayQuery(int $userId, CarbonImmutable $from): \Illuminate\Database\Query\Builder
    {
        return DB::table('trades')
            ->join('accounts', 'accounts.id', '=', 'trades.account_id')
            ->where('accounts.user_id', $userId)
            ->where('accounts.is_sample', false)
            // Un join de query builder no pasa por el scope global de SoftDeletes:
            // sin esto, una cuenta archivada seguiría alimentando las rachas.
            ->whereNull('accounts.deleted_at')
            ->where('trades.exit_time', '>=', $from->toDateTimeString())
            ->selectRaw('CAST(trades.exit_time AS date) AS day')
            ->groupBy('day');
    }

    /** ¿Alguno de estos días está en el conjunto? */
    private function anyDay(array $days, array $set): bool
    {
        foreach ($days as $day) {
            if (isset($set[$day])) {
                return true;
            }
        }

        return false;
    }

    /** Día laborable anterior: el fin de semana no cuenta ni rompe la racha. */
    private function previousWeekday(CarbonImmutable $day): CarbonImmutable
    {
        do {
            $day = $day->subDay();
        } while ($day->isWeekend());

        return $day;
    }
}
