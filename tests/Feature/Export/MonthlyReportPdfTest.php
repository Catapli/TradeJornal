<?php

declare(strict_types=1);

use App\Actions\Export\BuildMonthlyReport;
use App\Livewire\ReportsPage;
use App\Models\Account;
use App\Models\Mistake;
use App\Models\Trade;
use App\Models\TradeAsset;
use App\Models\TradingRule;
use App\Models\User;
use App\Services\Export\MonthlyReportPdf;
use App\Support\Demo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

/**
 * El PDF del informe mensual (Fase 5 · P10).
 *
 * Aquí se comprueban dos cosas distintas: que la plantilla dice lo que tiene que
 * decir —eso se mira en el HTML, que es legible— y que dompdf es capaz de
 * imprimirla de verdad. Lo segundo importa porque el motor es PHP puro y una
 * etiqueta que no soporta no falla: se la come en silencio.
 */
beforeEach(function () {
    $this->jordi = User::factory()->create(['trial_ends_at' => now()->addDays(10)]);
    $this->cuenta = Account::factory()->create([
        'user_id' => $this->jordi->id,
        'name' => 'FTMO 100k',
        'currency' => 'EUR',
    ]);
    $this->activo = TradeAsset::factory()->create();
    $this->agosto = CarbonImmutable::parse('2026-08-15');

    $this->actingAs($this->jordi);
    app()->setLocale('es');
});

function opPdf(string $cierre, float $pnl): Trade
{
    $salida = Carbon\Carbon::parse($cierre);

    return Trade::factory()->create([
        'account_id' => test()->cuenta->id,
        'trade_asset_id' => test()->activo->id,
        'pnl' => $pnl,
        'entry_time' => (clone $salida)->subHour(),
        'exit_time' => $salida,
    ]);
}

function informeDeAgosto(): array
{
    return app(BuildMonthlyReport::class)->execute(
        test()->jordi,
        test()->agosto,
        test()->cuenta
    );
}

function htmlDelInforme(array $informe, bool $demo = false): string
{
    return view('exports.monthly-report', [
        'report' => $informe,
        'logo' => null,
        'isDemo' => $demo,
    ])->render();
}

it('dompdf imprime el informe de verdad', function () {
    opPdf('2026-08-05 10:00', 400);
    opPdf('2026-08-06 10:00', -150);

    $binario = app(MonthlyReportPdf::class)->raw(informeDeAgosto());

    expect(substr($binario, 0, 5))->toBe('%PDF-')
        // Un PDF de una página con texto no baja de unos pocos kilobytes; si
        // saliera casi vacío es que la plantilla no llegó a renderizarse.
        ->and(strlen($binario))->toBeGreaterThan(3000);
});

it('el nombre del fichero lleva la cuenta y el mes', function () {
    $nombre = app(MonthlyReportPdf::class)->filename(informeDeAgosto());

    expect($nombre)->toBe('tradeforge-informe-ftmo-100k-2026-08.pdf');
});

it('la plantilla pinta las cifras, el calendario y la curva', function () {
    opPdf('2026-08-05 10:00', 400);
    opPdf('2026-08-06 10:00', -150);

    $html = htmlDelInforme(informeDeAgosto());

    expect($html)->toContain('FTMO 100k')
        ->and($html)->toContain(__('export.pdf.metrics.title'))
        ->and($html)->toContain(__('export.pdf.calendar.title'))
        ->and($html)->toContain('250,00 EUR')      // el P&L del mes, con su divisa
        ->and($html)->toContain('<polyline');       // la curva se dibujó
});

it('sin curva que dibujar lo dice en vez de enseñar un marco vacío', function () {
    // Un solo día de operaciones deja la serie en dos puntos; sin ninguno, en uno.
    $html = htmlDelInforme(informeDeAgosto());

    expect($html)->toContain(__('export.pdf.equity.empty'))
        ->and($html)->not->toContain('<polyline');
});

it('el coste de los errores nunca sale sin su cobertura', function () {
    $fomo = Mistake::create([
        'user_id' => $this->jordi->id,
        'slug' => 'fomo-pdf',
        'name' => 'FOMO',
        'color' => 'amber',
        'weight' => 2,
    ]);

    opPdf('2026-08-05 10:00', 300);
    opPdf('2026-08-06 10:00', -200)->mistakes()->sync([$fomo->id]);

    $html = htmlDelInforme(informeDeAgosto());

    expect($html)->toContain('FOMO')
        ->and($html)->toContain(__('export.pdf.mistakes.coverage', [
            'reviewed' => 1,
            'total' => 2,
            'coverage' => '50,0',
        ]));
});

it('una regla que se cumple a mano sale sin porcentaje inventado', function () {
    TradingRule::create([
        'user_id' => $this->jordi->id,
        'kind' => TradingRule::KIND_MISTAKE,
        'text' => 'No operar después de dos pérdidas seguidas',
        'config' => ['slot' => 'afternoon'],
        'source_key' => 'test',
        'source_summary' => 'Hallazgo de prueba',
        'source_sample' => 12,
        'is_active' => true,
    ]);

    opPdf('2026-08-05 10:00', 100);

    $html = htmlDelInforme(informeDeAgosto());

    expect($html)->toContain('No operar después de dos pérdidas seguidas')
        ->and($html)->toContain(__('export.pdf.rules.manual'));
});

it('el informe de la demo va sellado como demo', function () {
    opPdf('2026-08-05 10:00', 100);

    $html = htmlDelInforme(informeDeAgosto(), demo: true);

    expect($html)->toContain(__('export.pdf.demo_stamp'))
        ->and($html)->toContain(__('export.pdf.demo_footer'));
});

it('un usuario PRO se descarga el informe desde el Laboratorio', function () {
    opPdf('2026-08-05 10:00', 400);

    Livewire::test(ReportsPage::class)
        ->set('accountId', (string) $this->cuenta->id)
        ->set('reportMonth', '2026-08')
        ->call('downloadMonthlyReport')
        ->assertFileDownloaded('tradeforge-informe-ftmo-100k-2026-08.pdf');
});

it('un mes vacío avisa en lugar de descargar un PDF de ceros', function () {
    opPdf('2026-08-05 10:00', 400);

    Livewire::test(ReportsPage::class)
        ->set('accountId', (string) $this->cuenta->id)
        ->set('reportMonth', '2026-07')
        ->call('downloadMonthlyReport')
        ->assertNoFileDownloaded()
        ->assertDispatched('show-alert');
});

it('el desplegable solo ofrece meses con operaciones', function () {
    opPdf('2026-08-05 10:00', 400);
    opPdf('2026-06-05 10:00', 100);

    $meses = array_column(
        Livewire::test(ReportsPage::class)->set('accountId', (string) $this->cuenta->id)->instance()->reportMonths,
        'value'
    );

    expect($meses)->toBe(['2026-08', '2026-06']);
});

it('el muro PRO cierra el informe a un usuario gratuito', function () {
    $gratuito = User::factory()->create(['trial_ends_at' => null]);
    $suya = Account::factory()->create(['user_id' => $gratuito->id]);

    Trade::factory()->create([
        'account_id' => $suya->id,
        'trade_asset_id' => $this->activo->id,
        'exit_time' => Carbon\Carbon::parse('2026-08-05 10:00'),
        'entry_time' => Carbon\Carbon::parse('2026-08-05 09:00'),
        'pnl' => 100,
    ]);

    // Sin `set()` previo: la primera petición de Livewire ya corta, y encadenar
    // sobre un componente que no llegó a montarse rompe el propio test.
    Livewire::actingAs($gratuito)
        ->test(ReportsPage::class)
        ->call('downloadMonthlyReport')
        ->assertStatus(403);
});

it('la demo puede descargar el informe: solo lee', function () {
    config(['demo.enabled' => true]);
    Session::put(Demo::SESSION_KEY, true);

    opPdf('2026-08-05 10:00', 400);

    expect(Demo::active())->toBeTrue();

    Livewire::test(ReportsPage::class)
        ->set('accountId', (string) $this->cuenta->id)
        ->set('reportMonth', '2026-08')
        ->call('downloadMonthlyReport')
        ->assertFileDownloaded();
});
