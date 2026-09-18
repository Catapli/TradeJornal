<?php

declare(strict_types=1);

use App\Actions\Export\ExportTradesCsv;
use App\Livewire\TradesPage;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\User;
use App\Support\Demo;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

/**
 * El CSV de operaciones (Fase 5 · P10).
 *
 * Lo que se protege aquí es la promesa: el fichero trae exactamente las
 * operaciones que el usuario tiene delante, ni una más. Un CSV que ignorase los
 * filtros sería peor que no tenerlo, porque nadie comprueba un fichero de mil
 * filas antes de mandárselo a una prop firm.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create();
    $this->cuenta = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'name' => 'FTMO 100k',
    ]);
    // La factory de activos usa unique() sobre seis símbolos: se comparte uno.
    $this->activo = TradeAsset::factory()->create();
    $this->csv = app(ExportTradesCsv::class);

    $this->actingAs($this->jordi);
    app()->setLocale('es');
});

function opCsv(array $attrs = []): Trade
{
    return Trade::factory()->create(array_merge([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
    ], $attrs));
}

/** Vuelca la respuesta en streaming a una cadena, como haría el navegador. */
function volcarCsv(Symfony\Component\HttpFoundation\StreamedResponse $response): string
{
    ob_start();
    $response->sendContent();

    return (string) ob_get_clean();
}

it('exporta una fila por operación con la cabecera traducida', function () {
    opCsv(['ticket' => 'T-1', 'pnl' => 120.50]);
    opCsv(['ticket' => 'T-2', 'pnl' => -80]);

    $contenido = volcarCsv(
        $this->csv->response(Trade::query()->forUser($this->jordi->id), 'x.csv')
    );

    $lineas = array_values(array_filter(explode("\n", trim($contenido))));

    expect($lineas)->toHaveCount(3)                       // cabecera + dos operaciones
        ->and($lineas[0])->toContain('Ticket')
        ->and($lineas[0])->toContain('Errores')
        ->and($contenido)->toContain('T-1')
        ->and($contenido)->toContain('T-2')
        ->and($contenido)->toContain('FTMO 100k');
});

it('respeta los filtros de la pantalla y no baja lo que no se ve', function () {
    opCsv(['ticket' => 'GANA', 'pnl' => 300]);
    opCsv(['ticket' => 'PIERDE', 'pnl' => -300]);

    $componente = Livewire::test(TradesPage::class)
        ->set('filters.result', 'win')
        ->call('exportCsv');

    $contenido = base64_decode((string) data_get($componente->effects, 'download.content'));

    expect($contenido)->toContain('GANA')
        ->and($contenido)->not->toContain('PIERDE');
});

it('nunca exporta operaciones de otro usuario', function () {
    $otro = User::factory()->create();
    $suya = Account::factory()->create(['user_id' => $otro->id]);
    Trade::factory()->create([
        'account_id' => $suya->id,
        'trade_asset_id' => $this->activo->id,
        'ticket' => 'AJENA',
    ]);
    opCsv(['ticket' => 'MIA']);

    $componente = Livewire::test(TradesPage::class)->call('exportCsv');
    $contenido = base64_decode((string) data_get($componente->effects, 'download.content'));

    expect($contenido)->toContain('MIA')
        ->and($contenido)->not->toContain('AJENA');
});

it('lleva los errores marcados en su columna', function () {
    $revenge = Mistake::create([
        'user_id' => $this->jordi->id,
        'slug' => 'revenge-csv',
        'name' => 'Revancha',
        'color' => 'rose',
        'weight' => 3,
    ]);

    $trade = opCsv(['ticket' => 'CON-ERROR']);
    $trade->mistakes()->sync([$revenge->id]);

    $contenido = volcarCsv(
        $this->csv->response(Trade::query()->forUser($this->jordi->id), 'x.csv')
    );

    expect($contenido)->toContain('Revancha');
});

it('aplana los saltos de línea de las notas para no partir el fichero', function () {
    opCsv(['ticket' => 'NOTA', 'notes' => "Primera línea\nSegunda línea"]);

    $contenido = volcarCsv(
        $this->csv->response(Trade::query()->forUser($this->jordi->id), 'x.csv')
    );

    // Cabecera + una operación: si la nota hubiera partido la fila serían tres.
    expect(array_values(array_filter(explode("\n", trim($contenido)))))->toHaveCount(2)
        ->and($contenido)->toContain('Primera línea Segunda línea');
});

it('usa punto y coma en español, que es lo que espera su Excel', function () {
    opCsv(['ticket' => 'T-ES']);

    $es = volcarCsv($this->csv->response(Trade::query()->forUser($this->jordi->id), 'x.csv'));

    app()->setLocale('en');
    $en = volcarCsv($this->csv->response(Trade::query()->forUser($this->jordi->id), 'x.csv'));

    expect($es)->toContain(';')
        ->and(explode("\n", $en)[0])->not->toContain(';');
});

it('avisa en la primera línea de que la demo no son datos reales', function () {
    config(['demo.enabled' => true]);
    Session::put(Demo::SESSION_KEY, true);

    opCsv(['ticket' => 'DEMO-1']);

    $contenido = volcarCsv(
        $this->csv->response(Trade::query()->forUser($this->jordi->id), 'x.csv')
    );

    expect(explode("\n", $contenido)[0])->toContain(__('export.demo_notice'));
});

it('no descarga un fichero vacío: avisa de que no hay nada que exportar', function () {
    Livewire::test(TradesPage::class)
        ->set('filters.result', 'win')
        ->call('exportCsv')
        ->assertNoFileDownloaded()
        ->assertDispatched('error');
});

it('está abierto a un usuario gratuito: sus datos son suyos', function () {
    opCsv(['ticket' => 'GRATIS']);

    expect($this->jordi->fresh()->hasProAccess())->toBeFalse();

    Livewire::test(TradesPage::class)
        ->call('exportCsv')
        ->assertFileDownloaded();
});
