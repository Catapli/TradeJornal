<?php

declare(strict_types=1);

use App\Actions\Retention\BuildWeeklySummary;
use App\Mail\WeeklySummaryMail;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

/**
 * El correo, ya compuesto.
 *
 * Importa tanto lo que dice como en qué idioma lo dice: se escribe desde la cola,
 * sin sesión, y ahí el único idioma disponible es el que guarda `users.locale`.
 */
beforeEach(function () {
    $this->semana = CarbonImmutable::parse('2026-08-17');
    $this->activo = TradeAsset::factory()->create();
});

function conResumen(string $locale = 'es'): array
{
    $user = User::factory()->create([
        'name' => 'Jordi',
        'timezone' => 'Europe/Madrid',
        'locale' => $locale,
        'weekly_summary' => true,
    ]);

    $cuenta = Account::factory()->create(['user_id' => $user->id, 'is_sample' => false]);

    Trade::factory()->create([
        'account_id' => $cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => 342.5,
        'entry_time' => '2026-08-18 09:00:00',
        'exit_time' => '2026-08-18 11:00:00',
    ]);

    $summary = app(BuildWeeklySummary::class)->execute($user->refresh(), test()->semana);

    return [$user, $summary];
}

it('compone el correo con las cifras de la semana y la llamada a la revisión', function () {
    [$user, $summary] = conResumen();

    $html = (new WeeklySummaryMail($user, $summary))->render();

    expect($html)->toContain('342,50')
        ->and($html)->toContain(__('weekly.mail.title'))
        ->and($html)->toContain(route('weekly.review'))
        ->and($html)->toContain(__('weekly.mail.unsubscribe'));
});

it('incluye un enlace de baja firmado', function () {
    [$user, $summary] = conResumen();

    $html = (new WeeklySummaryMail($user, $summary))->render();

    expect($html)->toContain('/resumen-semanal/baja/' . $user->id)
        ->and($html)->toContain('signature=');
});

it('lo escribe en el idioma guardado del usuario, sin sesión de por medio', function () {
    Mail::fake();

    [$user, $summary] = conResumen('en');

    Mail::to($user)->send(new WeeklySummaryMail($user, $summary));

    Mail::assertQueued(WeeklySummaryMail::class, fn ($mail): bool => $mail->locale === 'en');
});

it('usa el idioma por defecto si el guardado no está soportado', function () {
    [$user, $summary] = conResumen();

    $user->forceFill(['locale' => 'zz'])->save();

    expect($user->refresh()->preferredLocale())->toBe(config('app.locale'));
});
