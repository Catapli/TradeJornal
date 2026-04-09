<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    private const DISK       = 'r2';
    private const URL_TTL    = 60;   // minutos
    private const CACHE_TTL  = 55;   // minutos (5 min de margen antes de que expire)

    // ── PATHS ────────────────────────────────────────────────────────────────

    public function tradeChartPath(int $userId, string $ticket): string
    {
        return "users/{$userId}/trades/{$ticket}/chart.json";
    }

    public function tradeAnalysisImagePath(int $userId, string $ticket, string $ext = 'png'): string
    {
        return "users/{$userId}/trades/{$ticket}/analysis.{$ext}";
    }

    public function tradeScreenshotPath(int $userId, string $ticket, string $ext = 'png'): string
    {
        return "users/{$userId}/trades/{$ticket}/screenshot.{$ext}";
    }

    public function strategyScreenshotPath(int $userId, int $strategyId, string $ext = 'png'): string
    {
        return "users/{$userId}/strategies/{$strategyId}/screenshot.{$ext}";
    }

    // ── ESCRITURA ────────────────────────────────────────────────────────────

    public function putJson(string $path, array $data): string
    {
        Storage::disk(self::DISK)->put($path, json_encode($data));
        return $path;
    }

    public function putFile(string $path, mixed $file): string
    {
        Storage::disk(self::DISK)->put($path, $file);
        return $path;
    }

    // ── LECTURA ──────────────────────────────────────────────────────────────

    public function exists(string $path): bool
    {
        return Storage::disk(self::DISK)->exists($path);
    }

    public function getJson(string $path): ?array
    {
        if (!$this->exists($path)) return null;

        return json_decode(
            Storage::disk(self::DISK)->get($path),
            true
        );
    }

    /**
     * Devuelve el contenido binario del archivo.
     * Útil para pasar imágenes directamente a la IA sin exponer URL.
     */
    public function getContents(string $path): ?string
    {
        if (!$this->exists($path)) return null;
        return Storage::disk(self::DISK)->get($path);
    }

    // ── URLS TEMPORALES ──────────────────────────────────────────────────────

    /**
     * Presigned URL con caché. Úsala siempre en listas/colecciones.
     */

    public function temporaryUrl(?string $path, int $minutes = self::URL_TTL): ?string
    {
        if (!$path) return null;

        return cache()->remember(
            'r2_url_' . md5($path),
            now()->addMinutes(self::CACHE_TTL),
            function () use ($path, $minutes): string {
                /** @var FilesystemAdapter $disk */
                $disk = Storage::disk(self::DISK);
                return $disk->temporaryUrl($path, now()->addMinutes($minutes));
            }
        );
    }

    public function getMimeType(string $path): string
    {
        $contents = Storage::disk(self::DISK)->get($path);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($contents) ?: 'image/png';
    }

    // ── ELIMINACIÓN ──────────────────────────────────────────────────────────

    public function delete(?string $path): void
    {
        if ($path) Storage::disk(self::DISK)->delete($path);
    }

    /**
     * Elimina toda la carpeta de un trade (json + imágenes).
     */
    public function deleteTradeFiles(int $userId, string $ticket): void
    {
        Storage::disk(self::DISK)->deleteDirectory("users/{$userId}/trades/{$ticket}");
    }

    /**
     * Elimina todos los archivos de un usuario (cuidado: irreversible).
     */
    public function deleteUserDirectory(int $userId): void
    {
        Storage::disk(self::DISK)->deleteDirectory("users/{$userId}");
    }

    /**
     * Elimina la carpeta de una estrategia.
     */
    public function deleteStrategyFiles(int $userId, int $strategyId): void
    {
        Storage::disk(self::DISK)->deleteDirectory("users/{$userId}/strategies/{$strategyId}");
    }
}
