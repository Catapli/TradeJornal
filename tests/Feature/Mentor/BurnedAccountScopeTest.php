<?php

declare(strict_types=1);

use App\Actions\Mentor\BuildTraderProfile;
use App\Actions\Mistakes\CountPendingReview;
use App\Actions\Rules\DetectFindings;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Carbon\Carbon;

/**
 * Quemada no es lo mismo que archivada.
 *
 * Una cuenta **quemada** guarda el historial más instructivo que tiene un
 * trader —es el que explica por qué se quemó— y quien la pierde sigue operando
 * igual al día siguiente. Dejarla fuera del Mentor, de los hallazgos y de la
 * cola de repaso borraba meses de perfil justo el día en que más falta hacía
 * mirarlos.
 *
 * Una cuenta **archivada** la aparta el usuario a propósito, así que sigue
 * fuera de todo salvo de donde la elija a mano.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-08-28 10:00:00');

    $this->jordi = User::factory()->create();
    $this->activo = TradeAsset::factory()->create();

    $this->viva = Account::factory()->create(['user_id' => $this->jordi->id, 'status' => 'active']);
    $this->quemada = Account::factory()->create(['user_id' => $this->jordi->id, 'status' => 'burned']);
    $this->archivada = Account::factory()->create(['user_id' => $this->jordi->id, 'status' => 'active']);

    $this->error = Mistake::create(['slug' => 'ambito_venganza', 'name' => 'Operar por venganza', 'color' => 'red', 'weight' => 5]);

    $this->actingAs($this->jordi);
});

afterEach(fn () => Carbon::setTestNow());

/** Perdedora con error marcado y ya repasada, en la cuenta que se indique. */
function opEnCuenta(Account $cuenta, string $fecha, ?Mistake $error = null, bool $repasada = true): Trade
{
    $trade = Trade::factory()->create([
        'account_id' => $cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => -120,
        'entry_time' => Carbon::parse($fecha . ' 09:00:00'),
        'exit_time' => Carbon::parse($fecha . ' 10:00:00'),
        'duration_minutes' => 60,
        'mistakes_reviewed_at' => $repasada ? Carbon::parse($fecha . ' 11:00:00') : null,
    ]);

    if ($error) {
        $trade->mistakes()->attach($error->id);
    }

    return $trade;
}

/** Marca las MIN_MARKS del mes en la cuenta dada, para superar la muestra mínima. */
function marcasSuficientes(Account $cuenta, string $mes): void
{
    for ($i = 0; $i < BuildTraderProfile::MIN_MARKS; $i++) {
        opEnCuenta($cuenta, $mes . '-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), test()->error);
    }
}

// ---------------------------------------------------------------------------
// MENTOR
// ---------------------------------------------------------------------------

it('cuenta las operaciones de una cuenta quemada en el perfil', function () {
    marcasSuficientes($this->quemada, '2026-07');

    $perfil = app(BuildTraderProfile::class)->execute($this->jordi->id);

    expect($perfil['has_enough_data'])->toBeTrue()
        ->and($perfil['marks'])->toBe(BuildTraderProfile::MIN_MARKS)
        ->and($perfil['top']['id'])->toBe($this->error->id);
});

it('deja fuera del perfil las operaciones de una cuenta archivada', function () {
    marcasSuficientes($this->archivada, '2026-07');
    $this->archivada->delete();

    $perfil = app(BuildTraderProfile::class)->execute($this->jordi->id);

    expect($perfil['has_enough_data'])->toBeFalse()
        ->and($perfil['marks'])->toBe(0);
});

it('no mezcla el perfil con las cuentas de otro usuario', function () {
    $ajena = Account::factory()->create(['user_id' => User::factory()->create()->id, 'status' => 'burned']);
    marcasSuficientes($ajena, '2026-07');

    $perfil = app(BuildTraderProfile::class)->execute($this->jordi->id);

    expect($perfil['marks'])->toBe(0);
});

// ---------------------------------------------------------------------------
// COLA DE REPASO
// ---------------------------------------------------------------------------

it('mete en la cola de repaso las perdedoras sin repasar de una cuenta quemada', function () {
    // Son justo las que explican por qué se quemó.
    opEnCuenta($this->quemada, '2026-08-10', null, repasada: false);

    expect(app(CountPendingReview::class)->execute($this->jordi->id))->toBe(1);
});

it('no mete en la cola las de una cuenta archivada', function () {
    opEnCuenta($this->archivada, '2026-08-10', null, repasada: false);
    $this->archivada->delete();

    expect(app(CountPendingReview::class)->execute($this->jordi->id))->toBe(0);
});

// ---------------------------------------------------------------------------
// HALLAZGOS
// ---------------------------------------------------------------------------

it('calcula los hallazgos también sobre las cuentas quemadas', function () {
    // La consulta grande del Laboratorio ya las contaba: eran los hallazgos los
    // que aplicaban otro criterio sobre las mismas operaciones.
    marcasSuficientes($this->quemada, '2026-07');

    $operaciones = Trade::forUser($this->jordi->id)->with('mistakes')->get();

    expect($operaciones)->toHaveCount(BuildTraderProfile::MIN_MARKS)
        ->and(app(DetectFindings::class)->execute($operaciones))->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// DECLARAR LA MEZCLA
// ---------------------------------------------------------------------------

it('declara sobre cuántas cuentas se calcula el perfil', function () {
    // Con las quemadas dentro, el perfil puede juntar cuentas de tamaños
    // distintos: el importe en euros deja de ser comparable y hay que decirlo.
    marcasSuficientes($this->viva, '2026-06');
    marcasSuficientes($this->quemada, '2026-07');

    expect(app(BuildTraderProfile::class)->execute($this->jordi->id)['accounts'])->toBe(2);
});

it('no declara mezcla con una sola cuenta', function () {
    marcasSuficientes($this->viva, '2026-07');

    expect(app(BuildTraderProfile::class)->execute($this->jordi->id)['accounts'])->toBe(1);
});
