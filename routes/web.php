<?php

use App\Http\Controllers\JournalImageController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\TradeChartController;
use App\Http\Controllers\TradeController;
use App\Http\Middleware\SetLocale;
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

// routes/web.php
Route::get('/csrf-refresh', fn() => response()->json(['token' => csrf_token()]))->name('csrf.refresh');




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

    // ? Ruta Journal Page
    Route::get('/journal', function () {
        return view('journal.index');
    })->name('journal');

    Route::get('/trades', function () {
        return view('trades.index');
    })->name('trades');

    Route::get('/reports', function () {
        return view('reports.index');
    })->name('reports');

    Route::get('/playbook', function () {
        return view('playbook.index');
    })->name('playbook');

    Route::get('/session', function () {
        return view('session.index');
    })->name('session');

    Route::get('/session-history', function () {
        return view('session-history.index');
    })->name('session-history');

    Route::get('/pricing', function () {
        return view('pricing.index');
    })->name('pricing');

    Route::get('/backtesting', function () {
        return view('backtesting.index');
    })->name('backtesting');

    Route::get('/checkout/success', function () {
        return redirect()->route('dashboard')->with('status', '¡Pago realizado con éxito! Bienvenido a Pro 🚀');
    })->name('checkout.success'); // <--- IMPORTANTE: Este es el nombre que busca Laravel

    Route::get('/checkout/cancel', function () {
        return redirect()->route('pricing')->with('error', 'El proceso de pago fue cancelado.');
    })->name('checkout.cancel');  // <--- Y este también



    //? API
    Route::get('/trades/data', [TradeController::class, 'data']);        //? Obtener Trades
    Route::get('/trades/dashboard', [TradeController::class, 'dashboard']);        //? Obtener Trades
    Route::post('/journal/upload-image', [JournalImageController::class, 'store'])
        ->middleware('auth')
        ->name('journal.upload');
});


Route::get('/trades/{trade}/chart-data', [TradeChartController::class, 'show'])
    ->middleware(['auth'])
    ->name('trades.chart-data');

// Grupo protegido por Autenticación Y SuperAdmin
Route::middleware(['auth', 'superadmin'])->group(function () {

    Route::get('/admin/prop-firms', function () {
        return view('admin.propfirm.index');
    })->name('manage-prop-frim');

    Route::get('/admin/logs', function () {
        return view('admin.logs.index');
    })->name('manage-logs');

    Route::get('/admin/panel', function () {
        return view('admin.panel.index');
    })->name('admin-panel');
});


Route::get('/health', function () {
    return response('ok', 200);
});
