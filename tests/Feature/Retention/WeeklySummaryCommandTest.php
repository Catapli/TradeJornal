<?php

declare(strict_types=1);

use App\Mail\WeeklySummaryMail;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * El correo del domingo (R1).
 *
 * El comando corre cada hora y decide a quién escribe mirando la hora *local*
 * de cada usuario. Aquí se fija esa ventana, porque un fallo se traduce en
 * correos duplicados o de madrugada, que es la forma más rápida de perder a
 * alguien.
 */
beforeEach(function () {
    Mail::fake();

    // Domingo 18:00 en Madrid.
    $this->travelTo('2026-08-23 18:05:00');

    $this->activo = TradeAsset::factory()->create();
});

function suscriptor(array $attributes = []): User
{
    $user = User::factory()->create(array_merge([
        'timezone' => 'Europe/Madrid',
        'locale' => 'es',
        'weekly_summary' => true,
    ], $attributes));

    $cuenta = Account::factory()->create(['user_id' => $user->id, 'is_sample' => false]);

    Trade::factory()->create([
        'account_id' => $cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => 120,
        'entry_time' => '2026-08-19 10:00:00',
        'exit_time' => '2026-08-19 12:00:00',
    ]);

    return $user->refresh();
}

it('escribe a quien tiene ahora mismo su domingo a las 18:00', function () {
    $jordi = suscriptor();

    $this->artisan('resumen:semanal')->assertSuccessful();

    Mail::assertQueued(WeeklySummaryMail::class, fn ($mail) => $mail->hasTo($jordi->email));
});

it('no escribe a quien todavía no ha llegado a esa hora en su huso', function () {
    // En Ciudad de México son las 10:05 del domingo: le toca dentro de ocho horas.
    suscriptor(['timezone' => 'America/Mexico_City']);

    $this->artisan('resumen:semanal')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('no escribe a quien lo tiene desactivado', function () {
    suscriptor(['weekly_summary' => false]);

    $this->artisan('resumen:semanal')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('no escribe dos veces la misma semana', function () {
    suscriptor();

    $this->artisan('resumen:semanal')->assertSuccessful();
    $this->artisan('resumen:semanal')->assertSuccessful();

    Mail::assertQueuedCount(1);
});

it('no manda un correo vacío cuando la semana no tuvo nada', function () {
    User::factory()->create(['timezone' => 'Europe/Madrid', 'weekly_summary' => true]);

    $this->artisan('resumen:semanal')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('no escribe al usuario de la demo', function () {
    suscriptor(['email' => config('demo.email')]);

    $this->artisan('resumen:semanal')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('no escribe a quien no ha verificado su correo', function () {
    suscriptor(['email_verified_at' => null]);

    $this->artisan('resumen:semanal')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('con --user y --force escribe fuera de la ventana horaria', function () {
    $this->travelTo('2026-08-19 11:00:00'); // miércoles

    $jordi = suscriptor();

    $this->artisan('resumen:semanal', ['--user' => $jordi->email, '--force' => true])->assertSuccessful();

    Mail::assertQueued(WeeklySummaryMail::class);
});

it('con --dry-run no envía nada ni marca al usuario', function () {
    $jordi = suscriptor();

    $this->artisan('resumen:semanal', ['--dry-run' => true])->assertSuccessful();

    Mail::assertNothingQueued();
    expect($jordi->refresh()->weekly_summary_sent_at)->toBeNull();
});

it('marca la fecha de envío para no repetirlo', function () {
    $jordi = suscriptor();

    $this->artisan('resumen:semanal')->assertSuccessful();

    expect($jordi->refresh()->weekly_summary_sent_at)->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────
// Baja desde el propio correo
// ─────────────────────────────────────────────────────────────

it('da de baja desde el enlace firmado, sin sesión', function () {
    $jordi = suscriptor();

    $url = URL::temporarySignedRoute('weekly.unsubscribe', now()->addDays(30), ['user' => $jordi->id]);

    $this->get($url)->assertOk();

    expect($jordi->refresh()->weekly_summary)->toBeFalse();
});

it('rechaza la baja si el enlace no viene firmado', function () {
    $jordi = suscriptor();

    $this->get(route('weekly.unsubscribe', ['user' => $jordi->id]))->assertForbidden();

    expect($jordi->refresh()->weekly_summary)->toBeTrue();
});
