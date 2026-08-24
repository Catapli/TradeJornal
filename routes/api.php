<?php

use App\Http\Controllers\Mt5SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;











Route::post('/mt5-sync', [App\Http\Controllers\Mt5SyncController::class, 'sync']);

Route::post('/mt5-reset', [App\Http\Controllers\Mt5SyncController::class, 'resetSync']);

// routes/api.php
Route::post('/mt5-refresh-charts', [Mt5SyncController::class, 'refreshCharts']);

Route::post('/mt5-update-chart', [Mt5SyncController::class, 'updateChart']);
