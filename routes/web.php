<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\CollabListController;
use App\Http\Controllers\ListsController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\RolsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\TownController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\TrafficController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ZoneController;
use App\Http\Middleware\SetLocale;
use App\Livewire\CollabsListPage;
use App\Livewire\TableComponent;
use App\Livewire\TownsTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::controller(SocialiteController::class)->group(function () {
    Route::get('auth/google', 'googleLogin')->name('auth.google');
    Route::get('auth/google-callback', 'googleAuthentication')->name('auth.google-callback');
});


// ? Rutas protegidas por si es admin
// Route::middleware('is_admin')->group(function () {



// ? Rutas protegidas por autenticación
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // ? Ruta AccountPage
    Route::get('/cuentas', function () {
        return view('accounts.index');
    })->name('cuentas');

    //? Ruta Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    //? Vista Rols
    Route::get('/rols', function () {
        return view('rols.index');
    })->name('rols')->middleware('check.permission:rols,r');

    //? Vista Towns
    Route::get('/municipios', function () {
        return view('towns.index');
    })->name('municipios')->middleware('check.permission:towns,r');

    //? Vista Logs
    Route::get('/logs', function () {
        return view('logs.index');
    })->name('logs')->middleware('check.permission:logs,r');

    //? Vista Users
    Route::get('/users', function () {
        return view('users.index');
    })->name('users')->middleware('check.permission:users,r');



    //? API
    Route::get('/trades/data', [TradeController::class, 'data']);        //? Obtener Trades
    Route::get('/trades/dashboard', [TradeController::class, 'dashboard']);        //? Obtener Trades
    Route::get('/logs/data', [LogController::class, 'index']);        //? Obtener Logs
    Route::get('/users/data', [UserController::class, 'data']);       //? Obtener Usuarios
    Route::get('/rols/data', [RolsController::class, 'data']);
});

Route::get('/health', function () {
    return response('ok', 200);
});
