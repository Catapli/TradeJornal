<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollabListController;
use App\Http\Controllers\ListsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\TownController;
use App\Http\Controllers\TrafficController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;






// Route::post('login', [AuthController::class, 'login']);

// Route::post('login/2fa-verify', [AuthController::class, 'verify2fa']);

// Route::post('loginGoogle', [AuthController::class, 'loginGoogle']);


// Route::post('send-notification', [NotificationController::class, 'sendPushNotification']);

Route::post('/mt5-sync', [App\Http\Controllers\Mt5SyncController::class, 'sync']);

Route::post('/mt5-reset', [App\Http\Controllers\Mt5SyncController::class, 'resetSync']);
