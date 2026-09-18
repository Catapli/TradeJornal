<?php

use App\Http\Controllers\Mt5SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Endpoints del agente de MetaTrader
|--------------------------------------------------------------------------
|
| Por aquí entran TODOS los datos del proyecto. Están fuera de `auth:sanctum`
| a propósito —el `.exe` no mantiene sesión—, así que la autenticación la hace
| `Mt5SyncController` con el `sync_token` en cada método: 401 si no cuadra, 403
| sin plan PRO y comprobación de que la cuenta es del usuario del token.
|
| Lo que faltaba era el freno. El limitador `mt5-sync` está definido en
| `AppServiceProvider` y lleva dos topes, por token y por IP; el porqué de los
| dos está explicado allí.
|
*/

Route::middleware('throttle:mt5-sync')->group(function () {
    Route::post('/mt5-sync', [Mt5SyncController::class, 'sync']);

    Route::post('/mt5-reset', [Mt5SyncController::class, 'resetSync']);

    Route::post('/mt5-refresh-charts', [Mt5SyncController::class, 'refreshCharts']);

    Route::post('/mt5-update-chart', [Mt5SyncController::class, 'updateChart']);
});
