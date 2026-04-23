<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;

final class GetStorageStats
{
    /** @return array<string, mixed> */
    public function execute(): array
    {
        $limitBytes = (int) config('tradeforge.storage_limit_bytes', 10 * 1024 ** 3);

        $raw = Cache::remember('admin.storage.r2_stats', now()->addMinutes(15), function (): array {
            return $this->fetchFromCloudflare();
        });

        $totalBytes  = (int) ($raw['uploadedBytes'] ?? 0);
        $totalFiles  = (int) ($raw['objectCount']   ?? 0);
        $usedPercent = $limitBytes > 0
            ? round(($totalBytes / $limitBytes) * 100, 1)
            : 0.0;

        return [
            'total_bytes'     => $totalBytes,
            'total_files'     => $totalFiles,
            'formatted_total' => Number::fileSize($totalBytes),
            'limit_bytes'     => $limitBytes,
            'formatted_limit' => Number::fileSize($limitBytes),
            'used_percent'    => $usedPercent,
            'top_users'       => [],   // No disponible sin tabla de metadatos
            'source'          => 'cloudflare',
            'error'           => $raw['error'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function fetchFromCloudflare(): array
    {
        $accountId = config('services.cloudflare.account_id');
        $token     = config('services.cloudflare.api_token');
        $bucket    = config('services.cloudflare.r2_bucket');

        if (! $accountId || ! $token || ! $bucket) {
            return ['error' => 'Credenciales de Cloudflare no configuradas.'];
        }

        $since = now()->subDay()->toIso8601String();
        $until = now()->toIso8601String();

        $query = <<<'GQL'
query R2Stats($accountId: String!, $bucket: String!, $since: Time!, $until: Time!) {
    viewer {
        accounts(filter: { accountTag: $accountId }) {
            r2StorageAdaptiveGroups(
                filter: {
                    bucketName: $bucket,
                    datetime_geq: $since,
                    datetime_leq: $until
                }
                limit: 1
            ) {
                max {
                    objectCount
                    payloadSize
                }
            }
        }
    }
}
GQL;

        $response = Http::withToken($token)
            ->timeout(10)
            ->post('https://api.cloudflare.com/client/v4/graphql', [
                'query'     => $query,
                'variables' => [
                    'accountId' => $accountId,
                    'bucket'    => $bucket,
                    'since'     => $since,
                    'until'     => $until,
                ],
            ]);

        // TEMPORAL: vuelca la respuesta completa al log
        logger()->info('R2 Analytics raw response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if ($response->failed()) {
            logger()->warning('R2 Analytics API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['error' => "Error API Cloudflare: HTTP {$response->status()}"];
        }

        $groups = data_get(
            $response->json(),
            'data.viewer.accounts.0.r2StorageAdaptiveGroups.0.max',
            []
        );

        if (empty($groups)) {
            return ['error' => 'Sin datos de R2 disponibles aún.'];
        }

        return [
            'uploadedBytes' => $groups['payloadSize'] ?? 0, // payloadSize es el campo real
            'objectCount'   => $groups['objectCount']  ?? 0,
        ];
    }
}
