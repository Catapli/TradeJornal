<?php

namespace App;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogActions
{
    /**
     * Inserta un log de información/acción en la base de datos
     */
    protected function insertLog(
        string $action,
        string $form = null,
        string $description = null,
        int $user_id = null,
        string $type = 'info'
    ) {
        Log::create([
            'user_id' => $user_id ?? Auth::id(),
            'type' => $type,
            'form' => $form,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
        ]);
    }

    /**
     * Inserta un log de error capturado en try-catch
     */
    protected function logError(
        \Throwable $exception,
        string $action,
        string $form = null,
        string $description = null,
        int $user_id = null
    ) {
        Log::create([
            'user_id' => $user_id ?? Auth::id(),
            'type' => 'error',
            'form' => $form,
            'action' => $action,
            'description' => $description,
            'exception_message' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'exception_trace' => $exception->getTraceAsString(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'resolved' => false,
        ]);

        // También reportar a Laravel para que aparezca en logs tradicionales
        report($exception);
    }

    /**
     * Marca un error como resuelto
     */
    protected function resolveError(int $logId, string $notes = null)
    {
        Log::where('id', $logId)->update([
            'resolved' => true,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }
}
