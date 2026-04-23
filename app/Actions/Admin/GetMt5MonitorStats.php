<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use Illuminate\Support\Facades\DB;

final class GetMt5MonitorStats
{
    // Una cuenta se considera "desincronizada" si lleva más de 2h sin sync
    private const STALE_HOURS = 2;

    /** @return array<string, mixed> */
    public function execute(): array
    {
        // Una sola query con todos los agregados que necesitamos
        $totals = DB::table('accounts')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*)                                                        AS total,
                COUNT(*) FILTER (WHERE sync = true)                             AS sync_enabled,
                COUNT(*) FILTER (WHERE sync = true AND sync_error = true)       AS with_errors,
                COUNT(*) FILTER (WHERE sync = true
                    AND sync_error = false
                    AND last_sync < NOW() - INTERVAL '2 hours')                 AS stale,
                COUNT(*) FILTER (WHERE sync = true
                    AND sync_error = false
                    AND last_sync >= NOW() - INTERVAL '2 hours')                AS healthy
            ")
            ->first();

        // Cuentas con error activo — detalle para la tabla
        $errorAccounts = DB::table('accounts')
            ->join('users', 'users.id', '=', 'accounts.user_id')
            ->whereNull('accounts.deleted_at')
            ->where('accounts.sync', true)
            ->where('accounts.sync_error', true)
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.mt5_login',
                'accounts.broker_name',
                'accounts.platform',
                'accounts.last_sync',
                'accounts.sync_error_message',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->orderByRaw('accounts.last_sync ASC NULLS FIRST')
            ->limit(20)
            ->get()
            ->toArray();

        // Cuentas desincronizadas (sin error explícito pero sin sync reciente)
        $staleAccounts = DB::table('accounts')
            ->join('users', 'users.id', '=', 'accounts.user_id')
            ->whereNull('accounts.deleted_at')
            ->where('accounts.sync', true)
            ->where('accounts.sync_error', false)
            ->where('accounts.last_sync', '<', now()->subHours(self::STALE_HOURS))
            ->orWhereNull('accounts.last_sync')
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.mt5_login',
                'accounts.broker_name',
                'accounts.platform',
                'accounts.last_sync',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->orderByRaw('accounts.last_sync ASC NULLS FIRST')
            ->limit(20)
            ->get()
            ->toArray();

        // Últimas 10 sincronizaciones exitosas
        $recentSyncs = DB::table('accounts')
            ->join('users', 'users.id', '=', 'accounts.user_id')
            ->whereNull('accounts.deleted_at')
            ->where('accounts.sync', true)
            ->where('accounts.sync_error', false)
            ->whereNotNull('accounts.last_sync')
            ->select([
                'accounts.name',
                'accounts.mt5_login',
                'accounts.broker_name',
                'accounts.last_sync',
                'accounts.current_balance',
                'accounts.currency',
                'users.name as user_name',
            ])
            ->orderByDesc('accounts.last_sync')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'totals'         => (array) $totals,
            'error_accounts' => $errorAccounts,
            'stale_accounts' => $staleAccounts,
            'recent_syncs'   => $recentSyncs,
            'stale_hours'    => self::STALE_HOURS,
        ];
    }
}
