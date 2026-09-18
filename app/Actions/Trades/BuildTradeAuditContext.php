<?php

namespace App\Actions\Trades;

use App\Actions\Mentor\BuildTraderProfile;
use App\Actions\Mentor\TrackMonthlyGoal;
use App\Models\ImprovementGoal;
use App\Models\Mistake;
use App\Models\Trade;
use App\Services\StorageService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Construye el bloque de datos que recibe la IA al auditar un trade.
 *
 * Vivía duplicado en TradeDetailModal y DashboardPage, y las dos copias habían
 * divergido: el mismo trade daba un veredicto distinto según desde dónde se abriera.
 * Además el contexto mandaba precios crudos (MAE/MFE) y el modelo derivaba los pips
 * él mismo equivocándose, así que aquí se calcula todo y allí solo se interpreta.
 */
class BuildTradeAuditContext
{
    /** Velas previas a la entrada que se inspeccionan para leer la estructura. */
    private const STRUCTURE_LOOKBACK = 60;

    private const STRUCTURE_MIN_CANDLES = 10;

    /** Velas posteriores al cierre que se revisan buscando barridos de liquidez. */
    private const POST_EXIT_CANDLES = 30;

    /** Memo del JSON de velas, compartido entre estructura y post-cierre. */
    private ?array $candles = null;

    public function __construct(private StorageService $storage) {}

    public function execute(Trade $trade): string
    {
        $this->candles = null;

        return implode("\n", [
            __('ai.labels.asset') . ": {$trade->tradeAsset->name}",
            __('ai.labels.type') . ': ' . strtoupper($trade->direction),
            __('ai.labels.entry') . ": {$trade->entry_price} | " . __('ai.labels.exit') . ": {$trade->exit_price}",
            __('ai.labels.result') . ": {$trade->pnl} $ (Lots: {$trade->size})",
            __('ai.labels.duration') . ": {$trade->duration_minutes} min",
            __('ai.labels.structure') . ': ' . $this->structure($trade),
            __('ai.labels.efficiency') . ': ' . $this->efficiency($trade),
            __('ai.labels.future') . ': ' . $this->postExit($trade),
            __('ai.labels.profile') . ': ' . $this->memory($trade),
        ]);
    }

    /**
     * La memoria del mentor (Fase 6 · P5).
     *
     * Sin esto la IA producía N auditorías sueltas: cada operación juzgada como si
     * fuera la primera del usuario. Aquí entran los errores que se repiten y el
     * objetivo al que se comprometió este mes, para que el veredicto pueda decir
     * «otra vez lo mismo» cuando toca.
     *
     * Un trade sin cuenta (los de los tests, y los que aún no se han guardado) no
     * tiene historial: se dice que no lo hay en vez de inventar un perfil vacío.
     */
    private function memory(Trade $trade): string
    {
        $userId = $trade->account?->user_id;

        if (!$userId) {
            return __('ai.labels.profile_none');
        }

        $perfil = app(BuildTraderProfile::class)->execute((int) $userId);

        if (!$perfil['has_enough_data']) {
            return __('ai.labels.profile_none');
        }

        $errores = array_map(
            fn (array $e): string => __('ai.labels.profile_mistake', [
                'name' => $e['name'],
                'count' => $e['count'],
                'trend' => __('ai.labels.trend_' . $e['trend']),
            ]),
            array_slice($perfil['mistakes'], 0, 3),
        );

        return implode(' · ', array_filter([
            implode(', ', $errores),
            $this->goalLine((int) $userId),
        ]));
    }

    /** El objetivo del mes en curso, con lo que lleva gastado. */
    private function goalLine(int $userId): ?string
    {
        $objetivo = ImprovementGoal::where('user_id', $userId)
            ->whereDate('month', CarbonImmutable::today()->startOfMonth())
            ->with('mistake')
            ->first();

        if (!$objetivo) {
            return null;
        }

        $avance = app(TrackMonthlyGoal::class)->progress($objetivo);

        // El error puede haberse borrado del catálogo: `mistake_id` se queda a null
        // (nullOnDelete) y el objetivo sobrevive sin nombre, que es lo que se quiere.
        $error = $objetivo->mistake;

        return __('ai.labels.profile_goal', [
            'name' => $error instanceof Mistake ? $error->display_name : '',
            'baseline' => $objetivo->baseline,
            'target' => $objetivo->target,
            'so_far' => $avance['so_far'],
        ]);
    }

    /**
     * Resumen compacto de excursión para listados (auditoría de sesión).
     * Devuelve null si al trade le faltan MAE/MFE.
     */
    public function excursionSummary(Trade $trade): ?string
    {
        if ($trade->mae_price === null || $trade->mfe_price === null) {
            return null;
        }

        $entry = (float) $trade->entry_price;
        $isLong = in_array(strtoupper($trade->direction), ['LONG', 'BUY'], true);
        $adverse = max(0.0, $isLong ? $entry - (float) $trade->mae_price : (float) $trade->mae_price - $entry);
        $favorable = max(0.0, $isLong ? (float) $trade->mfe_price - $entry : $entry - (float) $trade->mfe_price);

        $fmt = $this->distanceFormatter($this->pipFactor($trade));

        return 'MAE ' . $fmt($adverse) . ' / MFE ' . $fmt($favorable);
    }

    /**
     * Factor de conversión precio → pip derivado del propio broker (pips_traveled vs
     * distancia recorrida). Evita hardcodear el tick size de cada activo.
     */
    private function pipFactor(Trade $trade): ?float
    {
        $moved = abs((float) $trade->exit_price - (float) $trade->entry_price);
        $pips = (float) ($trade->pips_traveled ?? 0);

        return ($moved > 0 && $pips > 0) ? $pips / $moved : null;
    }

    /** Velas del trade, memoizadas: un solo JSON de R2 por análisis. */
    private function candles(Trade $trade): array
    {
        if ($this->candles !== null) {
            return $this->candles;
        }

        if (!$trade->chart_data_path) {
            return $this->candles = [];
        }

        $chartData = $this->storage->getJson($trade->chart_data_path);
        $timeframes = $chartData['timeframes'] ?? [];

        return $this->candles = $timeframes['5m'] ?? ($timeframes['1m'] ?? []);
    }

    /**
     * Estructura de mercado previa a la entrada, derivada del OHLC.
     * Sustituye al análisis visual: AiService manda texto plano, nunca una imagen.
     */
    private function structure(Trade $trade): string
    {
        $candles = $this->candles($trade);

        if (empty($candles)) {
            return __('labels.no_data_market');
        }

        $entryTs = Carbon::parse($trade->entry_time)->timestamp;
        $prior = array_values(array_filter($candles, fn ($c) => ($c['time'] ?? 0) < $entryTs));

        if (count($prior) < self::STRUCTURE_MIN_CANDLES) {
            return __('labels.data_candles_not_enough');
        }

        $window = array_slice($prior, -self::STRUCTURE_LOOKBACK);
        $highs = array_column($window, 'high');
        $lows = array_column($window, 'low');

        // El exe de MT5 podría mandar velas sin OHLC completo: sin extremos no hay rango.
        if (empty($highs) || empty($lows)) {
            return __('labels.data_candles_not_enough');
        }

        $rangeHigh = (float) max($highs);
        $rangeLow = (float) min($lows);
        $range = $rangeHigh - $rangeLow;
        $entry = (float) $trade->entry_price;

        $fmt = $this->distanceFormatter($this->pipFactor($trade));

        $lines = [
            __('ai.labels.prior_range', ['count' => count($window)]) . ': '
                . number_format($rangeLow, 5) . ' - ' . number_format($rangeHigh, 5)
                . ' (' . $fmt($range) . ')',
            __('ai.labels.entry_position') . ': '
                . number_format($range > 0 ? (($entry - $rangeLow) / $range) * 100 : 50, 0) . '%',
            __('ai.labels.distance_to_low') . ': ' . $fmt(max(0.0, $entry - $rangeLow)),
            __('ai.labels.distance_to_high') . ': ' . $fmt(max(0.0, $rangeHigh - $entry)),
        ];

        $last = end($window);
        $ema = $last['ema'] ?? null;

        if (is_numeric($ema)) {
            $lines[] = __('ai.labels.ema_context', [
                'value' => number_format((float) $ema, 5),
                'side' => $entry >= (float) $ema ? __('ai.labels.above') : __('ai.labels.below'),
            ]);
        }

        // Rango de la última vela antes de entrar vs la media: un ratio alto delata
        // haber entrado sobre un impulso ya extendido (persecución de precio).
        $ranges = array_map(fn ($c) => abs(($c['high'] ?? 0) - ($c['low'] ?? 0)), $window);
        $avgRange = array_sum($ranges) / count($ranges);

        if ($avgRange > 0) {
            $lastRange = abs(($last['high'] ?? 0) - ($last['low'] ?? 0));
            $lines[] = __('ai.labels.entry_candle', [
                'range' => $fmt($lastRange),
                'avg' => $fmt($avgRange),
                'ratio' => number_format($lastRange / $avgRange, 1),
            ]);
        }

        return implode(' | ', $lines);
    }

    /** Métricas de eficiencia (MAE/MFE/R:R) ya resueltas en pips y dólares. */
    private function efficiency(Trade $trade): string
    {
        $entry = (float) $trade->entry_price;
        $exit = (float) $trade->exit_price;
        $moved = abs($exit - $entry);

        if ($trade->mae_price === null || $trade->mfe_price === null || $moved <= 0) {
            return __('labels.no_data_market');
        }

        $isLong = in_array(strtoupper($trade->direction), ['LONG', 'BUY'], true);
        $adverse = max(0.0, $isLong ? $entry - (float) $trade->mae_price : (float) $trade->mae_price - $entry);
        $favorable = max(0.0, $isLong ? (float) $trade->mfe_price - $entry : $entry - (float) $trade->mfe_price);

        $pips = (float) ($trade->pips_traveled ?? 0);
        $perPip = $pips > 0 ? ((float) $trade->pnl) / $pips : null;
        $fmt = $this->distanceFormatter($this->pipFactor($trade), $perPip);

        $peak = max($favorable, $moved);

        return implode(' | ', [
            __('ai.labels.mae') . ': ' . $fmt($adverse),
            __('ai.labels.mfe') . ': ' . $fmt($favorable),
            __('ai.labels.captured') . ': ' . $fmt($moved),
            __('ai.labels.exit_efficiency') . ': ' . number_format($moved / $peak * 100, 1) . '%',
            __('ai.labels.real_rr') . ': ' . ($adverse > 0
                ? number_format($moved / $adverse, 2) . ':1'
                : __('ai.labels.no_latent_risk')),
        ]);
    }

    /** ¿El precio siguió a favor tras cerrar? Delata salidas prematuras y barridos. */
    private function postExit(Trade $trade): string
    {
        $candles = $this->candles($trade);

        if (empty($candles)) {
            return __('labels.no_data_market');
        }

        $exitTimestamp = Carbon::parse($trade->exit_time)->timestamp;
        $entryPrice = (float) $trade->entry_price;
        $isLong = in_array(strtoupper($trade->direction), ['LONG', 'BUY'], true);
        $maxFavorableAfterExit = 0;
        $foundExit = false;
        $candlesChecked = 0;

        foreach ($candles as $candle) {
            if (($candle['time'] ?? 0) < $exitTimestamp) {
                continue;
            }

            $foundExit = true;
            $candlesChecked++;

            $delta = $isLong ? ($candle['high'] - $entryPrice) : ($entryPrice - $candle['low']);
            if ($delta > $maxFavorableAfterExit) {
                $maxFavorableAfterExit = $delta;
            }
            if ($candlesChecked >= self::POST_EXIT_CANDLES) {
                break;
            }
        }

        if (!$foundExit) {
            return __('labels.not_data_close');
        }

        $originalMfe = abs(((float) ($trade->mfe_price ?? 0)) - $entryPrice);
        $threshold = $originalMfe > 0 ? ($originalMfe * 1.5) : ($entryPrice * 0.0005);

        // Igual que el resto del contexto: en pips, no en puntos crudos.
        $fmt = $this->distanceFormatter($this->pipFactor($trade));

        return $maxFavorableAfterExit > $threshold
            ? __('labels.liquidity_sweep', ['pointsMoved' => $fmt((float) $maxFavorableAfterExit)])
            : __('labels.no_good_movement');
    }

    /**
     * Formatea una distancia de precio en pips (y opcionalmente en dólares).
     * Sin pips_traveled no hay factor fiable: cae a puntos crudos antes que inventar.
     */
    private function distanceFormatter(?float $pipFactor, ?float $perPip = null): \Closure
    {
        return fn (float $distance): string => $pipFactor === null
            ? number_format($distance, 5) . ' pts'
            : number_format($distance * $pipFactor, 1) . ' pips'
              . ($perPip === null ? '' : ' (' . number_format($distance * $pipFactor * $perPip, 2) . ' $)');
    }
}
