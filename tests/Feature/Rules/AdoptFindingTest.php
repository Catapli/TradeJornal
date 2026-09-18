<?php

declare(strict_types=1);

use App\Livewire\FindingsPanel;
use App\Livewire\SessionPage;
use App\Models\Account;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\TradingPlan;
use App\Models\TradingRule;
use App\Models\User;
use Livewire\Livewire;

/**
 * De hallazgo a regla, y de regla a sesión en vivo.
 *
 * Lo importante: que la regla guarde **de dónde salió** (dentro de un mes, «no
 * operar después de las 13:00» sin cifra al lado es una manía), que baje al plan
 * de la cuenta cuando el semáforo puede vigilarla, y que aparezca en el checklist.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create(['trial_ends_at' => now()->addDays(10)]);
    $this->cuenta = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'name' => 'Darwinex 100k',
        'status' => 'active',
        'is_sample' => false,
        'initial_balance' => 10000,
    ]);
    $this->activo = TradeAsset::factory()->create();
});

/** Genera un patrón claro: pierde por las tardes, gana por las mañanas. */
function patronDeTardes(): void
{
    for ($i = 1; $i <= 20; $i++) {
        $dia = str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT);

        foreach ([['15:00:00', -150.0], ['09:00:00', 200.0]] as [$hora, $pnl]) {
            Trade::factory()->create([
                'account_id' => test()->cuenta->id,
                'trade_asset_id' => test()->activo->id,
                'pnl' => $pnl,
                'entry_time' => Carbon\Carbon::parse("2026-08-{$dia} {$hora}"),
                'exit_time' => Carbon\Carbon::parse("2026-08-{$dia} {$hora}")->addHour(),
            ]);
        }
    }
}

it('enseña el hallazgo con su frase y su muestra', function () {
    patronDeTardes();

    $panel = Livewire::actingAs($this->jordi)->test(FindingsPanel::class)->instance();

    $hallazgo = collect($panel->findings)->firstWhere('key', 'time_of_day');

    expect($hallazgo['sentence'])->toContain('3.000,00 €')
        ->and($hallazgo['sample'])->toBe(20)
        ->and($hallazgo['adopted'])->toBeFalse();
});

it('guarda la regla con la procedencia pegada', function () {
    patronDeTardes();

    Livewire::actingAs($this->jordi)
        ->test(FindingsPanel::class, ['accountId' => (string) $this->cuenta->id])
        ->call('startAdopting', 'time_of_day')
        ->call('adopt');

    $regla = TradingRule::firstOrFail();

    expect($regla->source_key)->toBe('time_of_day')
        ->and($regla->source_sample)->toBe(20)
        ->and($regla->source_summary)->not->toBeEmpty()
        ->and($regla->account_id)->toBe($this->cuenta->id)
        ->and($regla->is_active)->toBeTrue();
});

it('baja al plan de la cuenta la regla que el semáforo sabe vigilar', function () {
    patronDeTardes();

    Livewire::actingAs($this->jordi)
        ->test(FindingsPanel::class, ['accountId' => (string) $this->cuenta->id])
        ->call('startAdopting', 'time_of_day')
        ->call('adopt');

    $plan = TradingPlan::where('account_id', $this->cuenta->id)->firstOrFail();

    // La tarde (13-17) fuera deja la ventana 00:00-12:59.
    expect($plan->start_time)->toContain('00:00')
        ->and($plan->end_time)->toContain('12:59')
        ->and($plan->is_active)->toBeTrue();
});

it('una regla global no toca el plan de ninguna cuenta', function () {
    // Escribir un horario en cuatro planes a la vez pisaría lo que el usuario
    // haya puesto a mano en cada uno.
    patronDeTardes();

    Livewire::actingAs($this->jordi)
        ->test(FindingsPanel::class)
        ->call('startAdopting', 'time_of_day')
        ->set('scope', 'all')
        ->call('adopt');

    expect(TradingRule::firstOrFail()->account_id)->toBeNull()
        ->and(TradingPlan::count())->toBe(0);
});

it('adoptar dos veces el mismo hallazgo no duplica la regla', function () {
    patronDeTardes();

    $panel = Livewire::actingAs($this->jordi)
        ->test(FindingsPanel::class, ['accountId' => (string) $this->cuenta->id]);

    $panel->call('startAdopting', 'time_of_day')->call('adopt');
    $panel->call('startAdopting', 'time_of_day')->call('adopt');

    expect(TradingRule::count())->toBe(1);
});

it('marca el hallazgo como ya adoptado', function () {
    patronDeTardes();

    $panel = Livewire::actingAs($this->jordi)
        ->test(FindingsPanel::class, ['accountId' => (string) $this->cuenta->id]);

    $panel->call('startAdopting', 'time_of_day')->call('adopt');

    $hallazgo = collect($panel->instance()->findings)->firstWhere('key', 'time_of_day');

    expect($hallazgo['adopted'])->toBeTrue();
});

it('se puede desactivar sin perder de dónde salió', function () {
    $regla = TradingRule::create([
        'user_id' => $this->jordi->id,
        'account_id' => $this->cuenta->id,
        'kind' => TradingRule::KIND_MAX_TRADES,
        'text' => 'Máximo 2 operaciones al día',
        'config' => ['limit' => 2],
        'source_key' => 'trades_per_day',
        'source_summary' => 'A partir de la 3ª perdiste 4.000 €',
        'source_sample' => 30,
    ]);

    Livewire::actingAs($this->jordi)
        ->test(FindingsPanel::class)
        ->call('toggle', $regla->id);

    $regla->refresh();

    expect($regla->is_active)->toBeFalse()
        ->and($regla->source_summary)->toBe('A partir de la 3ª perdiste 4.000 €');
});

it('no deja tocar la regla de otro usuario', function () {
    $otro = User::factory()->create();

    $ajena = TradingRule::create([
        'user_id' => $otro->id,
        'account_id' => null,
        'kind' => TradingRule::KIND_WEEKDAY,
        'text' => 'No operar los jueves',
        'config' => ['weekday' => 4],
        'source_key' => 'weekday',
        'source_summary' => 'x',
        'source_sample' => 20,
    ]);

    Livewire::actingAs($this->jordi)
        ->test(FindingsPanel::class)
        ->call('remove', $ajena->id);

    expect(TradingRule::whereKey($ajena->id)->exists())->toBeTrue();
});

it('la regla llega al checklist de la sesión en vivo', function () {
    TradingRule::create([
        'user_id' => $this->jordi->id,
        'account_id' => $this->cuenta->id,
        'kind' => TradingRule::KIND_MISTAKE,
        'text' => 'Antes de entrar, comprobar que no estoy aguantando la perdedora',
        'config' => [],
        'source_key' => 'mistake_cost',
        'source_summary' => 'x',
        'source_sample' => 14,
    ]);

    $cuentas = Livewire::actingAs($this->jordi)->test(SessionPage::class)->instance()->accounts;

    $mia = collect($cuentas)->firstWhere('id', $this->cuenta->id);

    expect(collect($mia['rules'])->pluck('text'))
        ->toContain('Antes de entrar, comprobar que no estoy aguantando la perdedora');
});

it('una regla desactivada no aparece en la sesión', function () {
    TradingRule::create([
        'user_id' => $this->jordi->id,
        'account_id' => $this->cuenta->id,
        'kind' => TradingRule::KIND_WEEKDAY,
        'text' => 'No operar los jueves',
        'config' => ['weekday' => 4],
        'source_key' => 'weekday',
        'source_summary' => 'x',
        'source_sample' => 20,
        'is_active' => false,
    ]);

    $cuentas = Livewire::actingAs($this->jordi)->test(SessionPage::class)->instance()->accounts;

    expect(collect($cuentas)->firstWhere('id', $this->cuenta->id)['rules'])->toBe([]);
});

it('una regla global aparece en todas las cuentas', function () {
    $segunda = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'status' => 'active',
        'name' => 'FTMO 50k',
    ]);

    TradingRule::create([
        'user_id' => $this->jordi->id,
        'account_id' => null,
        'kind' => TradingRule::KIND_WEEKDAY,
        'text' => 'No operar los jueves',
        'config' => ['weekday' => 4],
        'source_key' => 'weekday',
        'source_summary' => 'x',
        'source_sample' => 20,
    ]);

    $cuentas = collect(Livewire::actingAs($this->jordi)->test(SessionPage::class)->instance()->accounts);

    expect(collect($cuentas->firstWhere('id', $this->cuenta->id)['rules'])->pluck('text'))->toContain('No operar los jueves')
        ->and(collect($cuentas->firstWhere('id', $segunda->id)['rules'])->pluck('text'))->toContain('No operar los jueves');
});

it('el Laboratorio pinta el panel de hallazgos', function () {
    patronDeTardes();

    $this->actingAs($this->jordi)
        ->get(route('reports'))
        ->assertOk()
        ->assertSee(__('rules.findings.title'))
        ->assertSee(__('rules.mine.title'));
});

it('corta en servidor a un usuario gratuito', function () {
    $gratuito = User::factory()->create(['trial_ends_at' => now()->subDay()]);

    Livewire::actingAs($gratuito)
        ->test(FindingsPanel::class)
        ->call('toggle', 1)
        ->assertForbidden();
});
