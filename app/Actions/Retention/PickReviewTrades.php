<?php

declare(strict_types=1);

namespace App\Actions\Retention;

use App\Models\Trade;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Las seis operaciones de la revisión semanal.
 *
 * Tres mejores y tres peores por resultado. No es una elección perezosa: son los
 * dos extremos los que enseñan algo —qué salió bien para repetirlo y qué salió
 * mal para no repetirlo—, mientras que las de en medio rara vez tienen historia.
 *
 * Con menos de seis operaciones en la semana se revisan todas, y ninguna aparece
 * dos veces aunque sea a la vez la mejor y la peor.
 */
final class PickReviewTrades
{
    public const PER_SIDE = 3;

    public function execute(User $user, CarbonImmutable $weekStart): Collection
    {
        $weekStart = $weekStart->startOfWeek();

        $trades = Trade::query()
            ->with('tradeAsset')
            ->whereHas('account', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('is_sample', false);
            })
            ->whereBetween('exit_time', [
                $weekStart->toDateTimeString(),
                $weekStart->endOfWeek()->toDateTimeString(),
            ])
            ->get();

        if ($trades->isEmpty()) {
            return collect();
        }

        $best = $trades->sortByDesc('pnl')->take(self::PER_SIDE);
        $worst = $trades->sortBy('pnl')->take(self::PER_SIDE)->reject(
            fn (Trade $trade): bool => $best->contains('id', $trade->id)
        );

        return $best->concat($worst)->values();
    }

    /** Las operaciones ya revisadas, recuperadas por sus identificadores. */
    public function byIds(User $user, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Trade::query()
            ->with('tradeAsset')
            ->whereHas('account', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('id', $ids)
            ->orderByDesc('pnl')
            ->get();
    }
}
