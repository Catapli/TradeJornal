<?php

declare(strict_types=1);

namespace App\Actions\Retention;

use App\Models\JournalEntry;
use App\Models\Trade;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

/**
 * Las cifras de una semana, en un array plano.
 *
 * Lo consumen el correo del domingo (R1) y la revisión guiada (R2), así que el
 * número que ve el usuario en el email es literalmente el mismo que verá al
 * entrar: si se calculara dos veces, tarde o temprano dirían cosas distintas.
 *
 * Las cuentas de ejemplo quedan fuera. Un resumen del rendimiento de datos
 * inventados no es un resumen, es ruido.
 */
final class BuildWeeklySummary
{
    /** Semana natural: lunes 00:00 → domingo 23:59:59 en la hora del usuario. */
    public function execute(User $user, CarbonImmutable $weekStart): array
    {
        $weekStart = $weekStart->startOfWeek();
        $weekEnd = $weekStart->endOfWeek();

        $trades = $this->trades($user, $weekStart, $weekEnd);
        $previous = $this->basics($this->trades($user, $weekStart->subWeek(), $weekStart->subWeek()->endOfWeek()));

        $journal = JournalEntry::where('user_id', $user->id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get();

        $basics = $this->basics($trades);

        return array_merge($basics, [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'best_trade' => $this->extreme($trades, desc: true),
            'worst_trade' => $this->extreme($trades, desc: false),
            'mistakes' => $this->mistakes($user, $weekStart, $weekEnd),
            'broken_rules' => $this->brokenRules($journal),
            'best_hour' => $this->bestHour($trades),
            'journal_days' => $journal->filter(fn (JournalEntry $e): bool => $this->isWritten($e))->count(),
            'discipline' => $this->discipline($journal),
            'previous' => $previous,
            // Sin operaciones y sin diario no hay nada que resumir: el comando
            // usa esto para no mandar un correo vacío, que es la forma más
            // rápida de que alguien se dé de baja.
            'has_activity' => $trades->isNotEmpty() || $journal->isNotEmpty(),
        ]);
    }

    /** Operaciones cerradas dentro de la semana, en cuentas reales del usuario. */
    private function trades(User $user, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Trade::query()
            ->with('tradeAsset')
            ->whereHas('account', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('is_sample', false);
            })
            ->whereBetween('exit_time', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->orderBy('exit_time')
            ->get();
    }

    /** @return array{trades:int,pnl:float,wins:int,losses:int,win_rate:float} */
    private function basics(Collection $trades): array
    {
        $wins = $trades->where('pnl', '>', 0)->count();
        $losses = $trades->where('pnl', '<', 0)->count();
        $decided = $wins + $losses;

        return [
            'trades' => $trades->count(),
            'pnl' => round((float) $trades->sum('pnl'), 2),
            'wins' => $wins,
            'losses' => $losses,
            // Las operaciones a cero no entran en el denominador: no fueron ni
            // acierto ni fallo y hundirían el porcentaje sin motivo.
            'win_rate' => $decided > 0 ? round($wins / $decided * 100, 1) : 0.0,
        ];
    }

    private function extreme(Collection $trades, bool $desc): ?array
    {
        if ($trades->isEmpty()) {
            return null;
        }

        $trade = $desc ? $trades->sortByDesc('pnl')->first() : $trades->sortBy('pnl')->first();

        return [
            'symbol' => $trade->tradeAsset?->symbol ?? '—',
            'pnl' => round((float) $trade->pnl, 2),
            'direction' => $trade->direction,
            'date' => CarbonImmutable::parse($trade->exit_time)->toDateString(),
        ];
    }

    /**
     * Errores repetidos de la semana, con lo que costaron.
     *
     * Se ordenan por peso y luego por repeticiones: dos «operar sin plan» pesan
     * más que cinco «entrada anticipada», y el correo solo tiene sitio para tres.
     */
    private function mistakes(User $user, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return DB::table('trade_mistake')
            ->join('trades', 'trades.id', '=', 'trade_mistake.trade_id')
            ->join('accounts', 'accounts.id', '=', 'trades.account_id')
            ->join('mistakes', 'mistakes.id', '=', 'trade_mistake.mistake_id')
            ->where('accounts.user_id', $user->id)
            ->where('accounts.is_sample', false)
            // Igual que en las rachas: el join esquiva el scope global, y un
            // correo que repasa una cuenta archivada no se entiende.
            ->whereNull('accounts.deleted_at')
            ->whereBetween('trades.exit_time', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->groupBy('mistakes.id', 'mistakes.name', 'mistakes.slug', 'mistakes.weight')
            ->select([
                'mistakes.slug',
                'mistakes.name',
                'mistakes.weight',
                DB::raw('COUNT(*) AS times'),
                DB::raw('SUM(trades.pnl) AS pnl'),
            ])
            ->orderByDesc('mistakes.weight')
            ->orderByDesc('times')
            ->limit(3)
            ->get()
            ->map(fn ($row): array => [
                'slug' => $row->slug,
                'name' => $this->mistakeName($row->slug, $row->name),
                'weight' => (int) $row->weight,
                'times' => (int) $row->times,
                'pnl' => round((float) $row->pnl, 2),
            ])
            ->all();
    }

    /** Nombre traducido del catálogo global; literal si lo creó el usuario. */
    private function mistakeName(?string $slug, string $name): string
    {
        $key = "mistakes.{$slug}.name";

        return $slug && Lang::has($key) ? __($key) : $name;
    }

    /** Objetivos del día que quedaron sin marcar, agrupados por texto. */
    private function brokenRules(Collection $journal): array
    {
        $broken = [];

        foreach ($journal as $entry) {
            foreach ($entry->daily_objectives ?? [] as $objective) {
                if (!empty($objective['done'])) {
                    continue;
                }

                $text = trim((string) ($objective['text'] ?? ''));

                if ($text === '') {
                    continue;
                }

                $broken[$text] = ($broken[$text] ?? 0) + 1;
            }
        }

        arsort($broken);

        return collect($broken)->take(3)
            ->map(fn (int $times, string $text): array => ['text' => $text, 'times' => $times])
            ->values()
            ->all();
    }

    /**
     * Franja horaria con más beneficio.
     *
     * Se agrupa por hora de **entrada**: lo que se elige es cuándo se abre, no
     * cuándo el mercado decide cerrarte.
     */
    private function bestHour(Collection $trades): ?array
    {
        if ($trades->isEmpty()) {
            return null;
        }

        $byHour = $trades->groupBy(fn (Trade $t): int => (int) CarbonImmutable::parse($t->entry_time)->hour)
            ->map(fn (Collection $group): array => [
                'trades' => $group->count(),
                'pnl' => round((float) $group->sum('pnl'), 2),
            ]);

        $hour = $byHour->sortByDesc('pnl')->keys()->first();

        return [
            'hour' => (int) $hour,
            'trades' => $byHour[$hour]['trades'],
            'pnl' => $byHour[$hour]['pnl'],
        ];
    }

    /** Media de disciplina de la semana, o null si no hay ningún día puntuado. */
    private function discipline(Collection $journal): ?float
    {
        $scores = $journal->pluck('discipline_score')->filter(fn ($s): bool => $s !== null);

        return $scores->isEmpty() ? null : round((float) $scores->avg(), 1);
    }

    private function isWritten(JournalEntry $entry): bool
    {
        return trim((string) $entry->content) !== ''
            || trim((string) $entry->pre_market_notes) !== ''
            || $entry->pre_market_mood !== null;
    }
}
