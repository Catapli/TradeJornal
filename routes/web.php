<?php

use App\Http\Controllers\DemoController;
use App\Http\Controllers\JournalImageController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\TradeChartController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\WeeklySummaryController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ? Landing pública. Antes esto redirigía a /login, así que no había forma de
// ? conocer el producto sin registrarse primero.
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('landing');

// ? Precios: visible sin sesión. El botón de compra pide login al pulsarlo.
Route::get('/pricing', function () {
    return view('pricing.index');
})->name('pricing');

// ? Demo pública de solo lectura (App\Http\Middleware\DemoGuard).
Route::get('/demo', [DemoController::class, 'enter'])->name('demo.enter');
Route::get('/demo/salir', [DemoController::class, 'exit'])->name('demo.exit');

// ? Baja del resumen semanal desde el propio correo: enlace firmado y sin sesión.
// ? Obligar a iniciar sesión para dejar de recibir un correo es la forma educada
// ? de no dejar que nadie se dé de baja.
Route::get('/resumen-semanal/baja/{user}', [WeeklySummaryController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('weekly.unsubscribe');

// ? PWA: manifiesto traducido y pantalla sin conexión. Ambas públicas — el
// ? service worker las pide sin garantía de sesión.
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sin-conexion', [PwaController::class, 'offline'])->name('pwa.offline');

Route::controller(SocialiteController::class)->group(function () {
    Route::get('auth/google', 'googleLogin')->name('auth.google');
    Route::get('auth/google-callback', 'googleAuthentication')->name('auth.google-callback');
});

// routes/web.php
Route::get('/csrf-refresh', fn () => response()->json(['token' => csrf_token()]))->name('csrf.refresh');

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

    // ? Ruta Dashboard
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

    // ? Importador de historiales. Disponible también en el plan gratuito: es la
    // ? puerta de entrada de los datos y cerrarla dejaba fuera a todo el que no
    // ? opera en MetaTrader.
    Route::get('/importar', function () {
        return view('import.index');
    })->name('trades.import');

    // ? Estado de la sincronización con MetaTrader: token, cuentas y diagnóstico.
    Route::get('/conexion', function () {
        return view('sync.index');
    })->name('sync.settings');

    // ? Repaso de errores, una operación por pantalla. NO es PRO: etiquetar es
    // ? entrada de datos, y cerrarla dejaría al usuario gratuito con el histórico
    // ? sin marcar justo el día que se suscribe. Mismo criterio que el importador.
    Route::get('/repaso', function () {
        return view('review.index');
    })->name('review');

    // ? Mentor con memoria (PRO): perfil acumulado de errores y un objetivo de
    // ? mejora al mes con seguimiento, en vez de N auditorías sueltas.
    Route::get('/mentor', function () {
        return view('mentor.index');
    })->name('mentor');

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

    // ? Revisión semanal guiada (PRO). El correo del domingo apunta aquí.
    Route::get('/revision-semanal', function () {
        return view('weekly.review');
    })->name('weekly.review');

    Route::get('/backtesting', function () {
        return view('backtesting.index');
    })->name('backtesting');

    Route::get('/checkout/success', function () {
        return redirect()->route('dashboard')->with('status', '¡Pago realizado con éxito! Bienvenido a Pro 🚀');
    })->name('checkout.success'); // <--- IMPORTANTE: Este es el nombre que busca Laravel

    Route::get('/checkout/cancel', function () {
        return redirect()->route('pricing')->with('error', 'El proceso de pago fue cancelado.');
    })->name('checkout.cancel');  // <--- Y este también

    // ? API
    Route::get('/trades/data', [TradeController::class, 'data']);        // ? Obtener Trades
    Route::get('/trades/dashboard', [TradeController::class, 'dashboard']);        // ? Obtener Trades
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
