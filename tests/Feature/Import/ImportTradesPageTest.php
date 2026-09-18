<?php

declare(strict_types=1);

use App\Livewire\ImportTradesPage;
use App\Models\Account;
use App\Models\ImportProfile;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

function uploadFixture(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        (string) file_get_contents(base_path('tests/Fixtures/import/' . $name)),
    );
}

function userWithAccount(): array
{
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id, 'initial_balance' => 50000]);

    return [$user, $account];
}

it('recorre el asistente entero y deja las operaciones dentro', function () {
    [$user, $account] = userWithAccount();

    Livewire::actingAs($user)
        ->test(ImportTradesPage::class)
        ->set('accountId', $account->id)
        ->set('file', uploadFixture('ctrader.csv'))
        ->call('analyze')
        ->assertSet('step', 'map')
        ->assertSet('presetKey', 'ctrader')
        ->assertSet('mapping.symbol', 'Symbol')
        ->call('goToPreview')
        ->assertSet('step', 'preview')
        ->call('import')
        ->assertSet('step', 'done')
        ->assertSet('result.imported', 3);

    expect(Trade::where('account_id', $account->id)->count())->toBe(3);
});

it('detecta el informe de MetaTrader y no pide emparejar nada', function () {
    [$user, $account] = userWithAccount();

    Livewire::actingAs($user)
        ->test(ImportTradesPage::class)
        ->set('accountId', $account->id)
        ->set('file', uploadFixture('mt4-statement.html'))
        ->call('analyze')
        ->assertSet('step', 'map')
        ->assertSet('presetKey', 'metatrader')
        ->assertSet('mapping.symbol', 'symbol')
        ->call('goToPreview')
        ->call('import')
        ->assertSet('result.imported', 2);
});

it('no deja continuar si falta un campo obligatorio', function () {
    [$user, $account] = userWithAccount();

    Livewire::actingAs($user)
        ->test(ImportTradesPage::class)
        ->set('accountId', $account->id)
        ->set('file', uploadFixture('generico.csv'))
        ->call('analyze')
        ->set('mapping.pnl', null)
        ->call('goToPreview')
        ->assertSet('step', 'map');
});

it('rechaza importar en una cuenta ajena', function () {
    [$user] = userWithAccount();
    $foreign = Account::factory()->create(['user_id' => User::factory()->create()->id]);

    Livewire::actingAs($user)
        ->test(ImportTradesPage::class)
        ->set('accountId', $foreign->id)
        ->set('file', uploadFixture('ctrader.csv'))
        ->call('analyze')
        ->assertSet('step', 'upload');

    expect(Trade::where('account_id', $foreign->id)->count())->toBe(0);
});

it('rechaza una extensión que no sabe leer', function () {
    [$user, $account] = userWithAccount();

    Livewire::actingAs($user)
        ->test(ImportTradesPage::class)
        ->set('accountId', $account->id)
        ->set('file', UploadedFile::fake()->create('historial.pdf', 10))
        ->call('analyze')
        ->assertHasErrors('file')
        ->assertSet('step', 'upload');
});

it('guarda el perfil de emparejamiento cuando se le pide', function () {
    [$user, $account] = userWithAccount();

    Livewire::actingAs($user)
        ->test(ImportTradesPage::class)
        ->set('accountId', $account->id)
        ->set('file', uploadFixture('ctrader.csv'))
        ->call('analyze')
        ->set('saveProfile', true)
        ->set('profileName', 'Mi cTrader')
        ->call('goToPreview')
        ->call('import');

    $profile = ImportProfile::where('user_id', $user->id)->first();

    expect($profile)->not->toBeNull()
        ->and($profile->name)->toBe('Mi cTrader')
        ->and($profile->preset)->toBe('ctrader')
        ->and($profile->mapping['symbol'])->toBe('Symbol');
});

it('aplica un perfil guardado ignorando columnas que este fichero no tiene', function () {
    [$user, $account] = userWithAccount();

    $profile = ImportProfile::create([
        'user_id' => $user->id,
        'name' => 'Antiguo',
        'preset' => 'generic',
        'mapping' => ['symbol' => 'Symbol', 'notes' => 'Columna Que Ya No Existe'],
        'pnl_includes_fees' => false,
        'decimal_mode' => 'comma',
    ]);

    Livewire::actingAs($user)
        ->test(ImportTradesPage::class)
        ->set('accountId', $account->id)
        ->set('file', uploadFixture('ctrader.csv'))
        ->call('analyze')
        ->set('loadedProfileId', $profile->id)
        ->call('applyProfile')
        ->assertSet('mapping.symbol', 'Symbol')
        ->assertSet('mapping.notes', null)
        ->assertSet('decimalMode', 'comma')
        ->assertSet('pnlIncludesFees', false);
});

it('el importador está disponible en el plan gratuito', function () {
    [$user] = userWithAccount();

    $this->actingAs($user)
        ->get(route('trades.import'))
        ->assertOk()
        ->assertSee(__('import.title'))
        ->assertDontSee(__('landing.gate.badge'));
});
