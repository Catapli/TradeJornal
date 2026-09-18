<?php

use App\Actions\Trades\BuildTradeAuditContext;
use App\Models\Trade;
use App\Services\StorageService;

/**
 * El contexto que recibe la IA al auditar un trade.
 *
 * El modelo derivaba los pips él mismo desde precios crudos y se equivocaba de orden
 * de magnitud (1.8 pips de MAE los reportaba como 118), lo que invertía el veredicto
 * de una operación buena. Toda esa aritmética vive ahora aquí y estos tests la fijan.
 */
beforeEach(function () {
    // La Action solo toca R2 si el trade tiene chart_data_path; en estos casos no lo tiene.
    $this->action = new BuildTradeAuditContext($this->mock(StorageService::class));
});

/** El trade real que destapó el bug: EURUSD long, 10 pips de TP, 1.8 de drawdown. */
function eurusdLong(array $overrides = []): Trade
{
    return Trade::factory()->make(array_merge([
        'direction' => 'long',
        'entry_price' => 1.16591,
        'exit_price' => 1.16691,
        'mae_price' => 1.16573,
        'mfe_price' => 1.16690,
        'pips_traveled' => 10.00,
        'pnl' => 142.50,
        'chart_data_path' => null,
    ], $overrides));
}

it('convierte MAE y MFE a pips y dólares en lugar de mandar precios crudos', function () {
    $context = $this->action->execute(eurusdLong());

    expect($context)
        ->toContain('1.8 pips (25.65 $)')   // MAE: 1.16591 - 1.16573
        ->toContain('9.9 pips (141.08 $)')  // MFE: 1.16690 - 1.16591
        ->toContain('10.0 pips (142.50 $)') // Recorrido capturado
        ->not->toContain('1.16573')         // el precio crudo ya no viaja al prompt
        ->not->toContain('1.16690');
});

it('calcula el R:R real a favor del trade', function () {
    // 0.00100 capturado / 0.00018 de drawdown = 5.56:1, no el 1.18:1 invertido que inventaba la IA.
    expect($this->action->execute(eurusdLong()))->toContain('5.56:1');
});

it('marca la eficiencia de salida al 100% cuando se cierra en el máximo', function () {
    expect($this->action->execute(eurusdLong()))->toContain('100.0%');
});

it('invierte el signo de MAE y MFE en los shorts', function () {
    $context = $this->action->execute(eurusdLong([
        'direction' => 'short',
        'entry_price' => 1.16691,
        'exit_price' => 1.16591,
        'mae_price' => 1.16709, // en contra de un short = por encima de la entrada
        'mfe_price' => 1.16592,
    ]));

    expect($context)->toContain('1.8 pips')->toContain('9.9 pips');
});

it('etiqueta el PnL en dólares para que no se confunda con distancias de precio', function () {
    expect($this->action->execute(eurusdLong()))->toContain('142.50 $');
});

it('cae a puntos crudos si el broker no mandó pips_traveled', function () {
    // Sin pips_traveled no hay factor de pip fiable: mejor puntos que un número inventado.
    $context = $this->action->execute(eurusdLong(['pips_traveled' => null]));

    expect($context)->toContain('pts')->not->toContain('pips');
});

it('no revienta cuando faltan MAE o MFE', function () {
    $context = $this->action->execute(eurusdLong(['mae_price' => null, 'mfe_price' => null]));

    expect($context)->toContain(__('labels.no_data_market'));
});

it('avisa de que no hay estructura cuando el trade no tiene velas', function () {
    expect($this->action->execute(eurusdLong()))
        ->toContain(__('ai.labels.structure'))
        ->toContain(__('labels.no_data_market'));
});

it('resume la excursión en una línea para la auditoría de sesión', function () {
    expect($this->action->excursionSummary(eurusdLong()))
        ->toBe('MAE 1.8 pips / MFE 9.9 pips');
});

it('devuelve null en el resumen de excursión si el trade no tiene MAE', function () {
    expect($this->action->excursionSummary(eurusdLong(['mae_price' => null])))->toBeNull();
});
