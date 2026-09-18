<?php

declare(strict_types=1);

use App\Actions\Mistakes\CountPendingReview;
use App\Livewire\ReviewPage;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

/**
 * Repaso de errores, una operación por pantalla (Fase 6 · usabilidad).
 *
 * Sustituye a la cola de chips del Laboratorio, y hereda de ella lo que había que
 * proteger: que solo entren **perdedoras sin repasar**, que nadie toque la
 * operación de otro, y que se pueda salir de la cola **sin inventarse un error**.
 * Si esa última salida no existiera, el usuario acabaría marcando cualquier cosa
 * y el coste de los errores mediría ruido.
 */
beforeEach(function () {
    Carbon::setTestNow('2026-08-28 10:00:00');

    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create(['user_id' => $this->jordi->id, 'status' => 'active']);
    $this->activo = TradeAsset::factory()->create();
    $this->error = Mistake::create(['slug' => 'repaso_venganza', 'name' => 'Operar por venganza', 'color' => 'red', 'weight' => 5]);
});

afterEach(fn () => Carbon::setTestNow());

/** Operación de la cuenta del usuario, con el resultado que se le pase. */
function opDeRepaso(float $pnl, array $extra = []): Trade
{
    return Trade::factory()->create(array_merge([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => Carbon::parse('2026-08-20 09:00:00'),
        'exit_time' => Carbon::parse('2026-08-20 10:00:00'),
        'duration_minutes' => 60,
        'mistakes_reviewed_at' => null,
    ], $extra));
}

it('solo mete en la cola las perdedoras sin repasar', function () {
    $pendiente = opDeRepaso(-100);
    opDeRepaso(300);                                                  // ganadora
    opDeRepaso(-50, ['mistakes_reviewed_at' => now()]);               // ya repasada
    opDeRepaso(-70)->mistakes()->attach($this->error->id);            // ya etiquetada

    Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->assertSet('queue', [$pendiente->id]);
});

it('congela la cola al entrar para que el contador no mienta', function () {
    // Etiquetar una operación la saca de la consulta de pendientes. Si la lista se
    // recalculara en cada paso, «3 de 9» pasaría a «3 de 8» a mitad de repaso.
    opDeRepaso(-100);
    opDeRepaso(-200, ['entry_time' => '2026-08-21 09:00:00', 'exit_time' => '2026-08-21 10:00:00']);

    $pantalla = Livewire::actingAs($this->jordi)->test(ReviewPage::class);

    expect($pantalla->instance()->total())->toBe(2);

    $pantalla->call('markClean');

    expect($pantalla->instance()->total())->toBe(2)
        ->and($pantalla->get('index'))->toBe(1);
});

it('deja salir de la cola sin inventarse un error', function () {
    $trade = opDeRepaso(-100);

    Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->call('markClean');

    $trade->refresh();

    expect($trade->mistakes_reviewed_at)->not->toBeNull()
        ->and($trade->mistakes)->toBeEmpty();
});

it('cierra el repaso conservando lo que se haya marcado', function () {
    $trade = opDeRepaso(-100);
    $trade->mistakes()->attach($this->error->id);

    Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->set('queue', [$trade->id])
        ->call('markReviewed');

    $trade->refresh();

    expect($trade->mistakes_reviewed_at)->not->toBeNull()
        ->and($trade->mistakes->pluck('id')->all())->toBe([$this->error->id]);
});

it('avanza a la siguiente al cerrar una', function () {
    opDeRepaso(-100);
    opDeRepaso(-200, ['entry_time' => '2026-08-21 09:00:00', 'exit_time' => '2026-08-21 10:00:00']);

    Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->assertSet('index', 0)
        ->call('markClean')
        ->assertSet('index', 1)
        ->assertSet('done', 1);
});

it('saltar no repasa nada y la deja en la cola', function () {
    $trade = opDeRepaso(-100);

    Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->call('skip')
        ->assertSet('index', 1)
        ->assertSet('done', 0);

    expect($trade->refresh()->mistakes_reviewed_at)->toBeNull();
});

it('se puede volver atrás', function () {
    opDeRepaso(-100);
    opDeRepaso(-200, ['entry_time' => '2026-08-21 09:00:00', 'exit_time' => '2026-08-21 10:00:00']);

    Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->call('skip')
        ->assertSet('index', 1)
        ->call('back')
        ->assertSet('index', 0)
        ->call('back')
        ->assertSet('index', 0); // no se pasa de rosca
});

it('le dice al visor qué operación toca al avanzar', function () {
    // El contenedor del gráfico lleva `wire:ignore`, así que el cambio de
    // operación no le llega solo: si el evento desaparece, el gráfico se queda
    // congelado en la primera y nadie se entera.
    // La cola va de la más reciente a la más antigua, así que la segunda del
    // repaso es la del día 20: es esa la que tiene que anunciarse al saltar.
    opDeRepaso(-100, ['mae_price' => 1.09850, 'mfe_price' => 1.10520]);
    $segunda = opDeRepaso(-200, [
        'entry_time' => '2026-08-21 09:00:00',
        'exit_time' => '2026-08-21 10:00:00',
    ]);

    Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->call('skip')
        ->assertDispatched(
            'trade-selected',
            fn (string $evento, array $datos): bool => (float) $datos['mae'] === 1.0985
                && (float) $datos['mfe'] === 1.1052,
        );

    expect($segunda->exists)->toBeTrue();
});

it('no repasa la operación de otro usuario', function () {
    // El id no llega del navegador, pero la cola sí viaja en el estado de Livewire.
    $ajena = Trade::factory()->create([
        'account_id' => Account::factory()->create()->id,
        'trade_asset_id' => $this->activo->id,
        'pnl' => -100,
        'mistakes_reviewed_at' => null,
    ]);

    Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->set('queue', [$ajena->id])
        ->call('markClean');

    expect($ajena->refresh()->mistakes_reviewed_at)->toBeNull();
});

it('vuelve a buscar pendientes al terminar la tanda', function () {
    $trade = opDeRepaso(-100);

    $pantalla = Livewire::actingAs($this->jordi)
        ->test(ReviewPage::class)
        ->call('markClean')
        ->assertSet('index', 1);

    // Entra una nueva mientras estabas repasando.
    opDeRepaso(-90, ['entry_time' => '2026-08-22 09:00:00', 'exit_time' => '2026-08-22 10:00:00']);

    $pantalla->call('reload')
        ->assertSet('index', 0)
        ->assertSet('done', 0);

    expect($pantalla->get('queue'))->toHaveCount(1)
        ->and($trade->refresh()->mistakes_reviewed_at)->not->toBeNull();
});

it('pinta la pantalla con el gráfico y el selector', function () {
    opDeRepaso(-100);

    $this->actingAs($this->jordi)
        ->get(route('review'))
        ->assertOk()
        ->assertSee(__('review.title'))
        ->assertSee(__('mistake_cost.review.clean'))
        ->assertSee(__('labels.errors_audit'));
});

it('felicita en vez de dejar una pantalla vacía', function () {
    opDeRepaso(300); // solo ganadoras: nada que repasar

    $this->actingAs($this->jordi)
        ->get(route('review'))
        ->assertOk()
        ->assertSee(__('review.empty_title'));
});

it('está abierto a un usuario gratuito', function () {
    // Etiquetar es entrada de datos. Cerrarla dejaría el histórico sin marcar
    // justo el día que se suscribe, que es cuando el Mentor tendría que hablarle.
    $gratuito = User::factory()->create(['trial_ends_at' => now()->subDay()]);
    $suCuenta = Account::factory()->create(['user_id' => $gratuito->id, 'status' => 'active']);

    Trade::factory()->create([
        'account_id' => $suCuenta->id,
        'trade_asset_id' => $this->activo->id,
        'pnl' => -100,
        'mistakes_reviewed_at' => null,
    ]);

    $this->actingAs($gratuito)
        ->get(route('review'))
        ->assertOk()
        ->assertSee(__('review.title'))
        ->assertDontSee(__('landing.gate.badge'));
});

it('el contador de pendientes se entera de que has repasado', function () {
    opDeRepaso(-100);

    $contador = app(CountPendingReview::class);

    expect($contador->execute($this->jordi->id))->toBe(1);

    Livewire::actingAs($this->jordi)->test(ReviewPage::class)->call('markClean');

    expect($contador->execute($this->jordi->id))->toBe(0);
});
