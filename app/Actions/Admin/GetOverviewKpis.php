<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class GetOverviewKpis
{
    /** @return array<string, int> */
    public function execute(): array
    {
        return Cache::remember('admin.overview.kpis', now()->addMinutes(5), function (): array {

            // Subquery reutilizable para usuarios con trades recientes
            $activeUsersQuery = fn(int $days): int => DB::table('trades')
                ->join('accounts', 'accounts.id', '=', 'trades.account_id')
                ->whereNull('accounts.deleted_at')
                ->where('trades.created_at', '>=', now()->subDays($days))
                ->distinct('accounts.user_id')
                ->count('accounts.user_id');

            return [
                // ── Usuarios ──────────────────────────────────────────
                'total_users'    => User::count(),
                'new_today'      => User::whereDate('created_at', today())->count(),

                // ── Actividad real de trading ──────────────────────────
                'active_7d'      => $activeUsersQuery(7),
                'active_30d'     => $activeUsersQuery(30),

                // ── Datos de la plataforma ─────────────────────────────
                'total_trades'   => DB::table('trades')->count(),
                'total_accounts' => DB::table('accounts')->whereNull('deleted_at')->count(),
                'total_sessions' => DB::table('trading_sessions')->count(),

                // ── Suscripciones ──────────────────────────────────────
                'pro_users'      => DB::table('subscriptions')
                    ->where('stripe_status', 'active')
                    ->distinct('user_id')
                    ->count('user_id'),

                'free_users'     => User::whereNotIn('id', function ($query) {
                    $query->select('user_id')
                        ->from('subscriptions')
                        ->where('stripe_status', 'active');
                })->count(),
            ];
        });
    }
}
