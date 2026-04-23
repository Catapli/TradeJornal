<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class GetQueueStats
{
    /** @return array<string, mixed> */
    public function execute(): array
    {
        return [
            'failed_count'  => $this->countFailedJobs(),
            'pending_count' => $this->countPendingJobs(),
            'last_job'      => $this->getLastJob(),
            'horizon_stats' => Cache::get('horizon.stats'), // null si Horizon no está instalado
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIVATE
    // ─────────────────────────────────────────────────────────────────────

    private function countFailedJobs(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    private function countPendingJobs(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        // Un job está "pendiente" si available_at <= ahora
        // (los delayed jobs con available_at en el futuro no cuentan)
        return (int) DB::table('jobs')
            ->where('available_at', '<=', now()->timestamp)
            ->count();
    }

    /** @return array<string, mixed>|null */
    private function getLastJob(): ?array
    {
        if (! Schema::hasTable('jobs')) {
            return null;
        }

        $job = DB::table('jobs')
            ->orderByDesc('id')
            ->first();

        if (! $job) {
            return null;
        }

        // El payload es JSON — extraemos solo el nombre de la clase
        // para no enviar objetos serializados enteros al frontend
        $payload   = json_decode($job->payload, true);
        $className = data_get($payload, 'displayName', 'Desconocido');

        // Nos quedamos solo con la parte final del namespace
        // Ej: "App\Jobs\SyncTradesFromMT5" → "SyncTradesFromMT5"
        $shortName = class_basename($className);

        return [
            'name'      => $shortName,
            'queue'     => $job->queue,
            'attempts'  => (int) $job->attempts,
            'created'   => $job->created_at,       // timestamp unix
            'available' => $job->available_at,     // timestamp unix
        ];
    }
}
