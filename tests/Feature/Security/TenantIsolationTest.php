<?php

use App\Livewire\AccountPage;
use App\Livewire\TradesPage;
use App\Models\Account;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

/**
 * Separación de datos entre usuarios.
 *
 * Es la única propiedad de seguridad que un SaaS multi-tenant no puede permitirse
 * fallar, y aquí dependía de recordar escribir `where('user_id', Auth::id())` en 81
 * sitios distintos. Estos tests convierten "está bien porque me acordé" en "está
 * bien porque hay algo que lo comprueba".
 *
 * Cada caso ataca un componente con el id de otro usuario. Ninguno debe filtrar
 * datos ni permitir escrituras.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->intruso = User::factory()->create();

    $this->cuentaJordi = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'name' => 'Cuenta privada de Jordi',
    ]);

    $this->tradeJordi = Trade::factory()->create([
        'account_id' => $this->cuentaJordi->id,
        'pnl' => 999.99,
    ]);
});

// ---------------------------------------------------------------------------
// POLICIES: la base sobre la que se apoya findOwned()
// ---------------------------------------------------------------------------

it('la policy de cuentas niega el acceso a un usuario ajeno', function () {
    expect($this->jordi->can('update', $this->cuentaJordi))->toBeTrue()
        ->and($this->intruso->can('update', $this->cuentaJordi))->toBeFalse()
        ->and($this->intruso->can('view', $this->cuentaJordi))->toBeFalse()
        ->and($this->intruso->can('delete', $this->cuentaJordi))->toBeFalse();
});

it('la policy de trades niega el acceso a un usuario ajeno', function () {
    expect($this->jordi->can('update', $this->tradeJordi))->toBeTrue()
        ->and($this->intruso->can('update', $this->tradeJordi))->toBeFalse()
        ->and($this->intruso->can('view', $this->tradeJordi))->toBeFalse()
        ->and($this->intruso->can('delete', $this->tradeJordi))->toBeFalse();
});

// ---------------------------------------------------------------------------
// ESCRITURAS CRUZADAS
// ---------------------------------------------------------------------------

it('no deja editar las reglas de riesgo de una cuenta ajena', function () {
    // `editingAccountId` es una propiedad pública: el cliente la controla.
    Livewire::actingAs($this->intruso)
        ->test(AccountPage::class)
        ->set('editingAccountId', $this->cuentaJordi->id)
        ->set('rules_max_loss_percent', 99)
        ->call('saveRules');

    expect($this->cuentaJordi->fresh()->tradingPlan)->toBeNull();
});

it('no deja actualizar una cuenta ajena', function () {
    Livewire::actingAs($this->intruso)
        ->test(AccountPage::class)
        ->call('updateAccount', $this->cuentaJordi->id);

    expect($this->cuentaJordi->fresh()->name)->toBe('Cuenta privada de Jordi');
});

it('no deja editar un trade ajeno', function () {
    Livewire::actingAs($this->intruso)
        ->test(TradesPage::class)
        ->set('isEditMode', true)
        ->set('editingTradeId', $this->tradeJordi->id)
        ->call('save');

    expect($this->tradeJordi->fresh()->pnl)->toEqual(999.99);
});

// ---------------------------------------------------------------------------
// LECTURAS CRUZADAS
// ---------------------------------------------------------------------------

it('no lista los trades de otro usuario', function () {
    $propio = Trade::factory()->create([
        'account_id' => Account::factory()->create(['user_id' => $this->intruso->id])->id,
    ]);

    Livewire::actingAs($this->intruso)
        ->test(TradesPage::class)
        ->assertSee($propio->ticket)
        ->assertDontSee($this->tradeJordi->ticket);
});

it('no abre el detalle de un trade ajeno', function () {
    Livewire::actingAs($this->intruso)
        ->test(TradesPage::class)
        ->call('openTradeDetail', $this->tradeJordi->id)
        ->assertNotDispatched('open-trade-detail');
});

it('el scope forUser deja fuera los trades de otras cuentas', function () {
    $ajeno = Trade::factory()->create([
        'account_id' => Account::factory()->create(['user_id' => $this->intruso->id])->id,
    ]);

    $visibles = Trade::forUser($this->jordi->id)->pluck('id');

    expect($visibles)->toContain($this->tradeJordi->id)
        ->and($visibles)->not->toContain($ajeno->id);
});

// ---------------------------------------------------------------------------
// EL MECANISMO
// ---------------------------------------------------------------------------

it('findOwned lanza AuthorizationException con un id de otro usuario', function () {
    $componente = new class
    {
        use App\Concerns\AuthorizesOwnership;
        use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

        public function abrir(int $id)
        {
            return $this->findOwned(App\Models\Account::class, $id, 'update');
        }
    };

    $this->actingAs($this->intruso);

    expect(fn () => $componente->abrir($this->cuentaJordi->id))
        ->toThrow(AuthorizationException::class);
});

it('findOwned devuelve el modelo cuando sí es del usuario', function () {
    $componente = new class
    {
        use App\Concerns\AuthorizesOwnership;
        use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

        public function abrir(int $id)
        {
            return $this->findOwned(App\Models\Account::class, $id, 'update');
        }
    };

    $this->actingAs($this->jordi);

    expect($componente->abrir($this->cuentaJordi->id)->id)->toBe($this->cuentaJordi->id);
});

it('findOwned trata un id manipulado como inexistente', function () {
    $componente = new class
    {
        use App\Concerns\AuthorizesOwnership;
        use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

        public function abrir(mixed $id)
        {
            return $this->findOwned(App\Models\Account::class, $id, 'update');
        }
    };

    $this->actingAs($this->jordi);

    expect(fn () => $componente->abrir('1 OR 1=1'))
        ->toThrow(Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});
