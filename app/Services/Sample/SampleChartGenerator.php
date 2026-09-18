<?php

declare(strict_types=1);

namespace App\Services\Sample;

use App\Models\Trade;
use Carbon\CarbonImmutable;

/**
 * Velas de ejemplo para las operaciones de la demo (Fase 6 · P8).
 *
 * El reproductor barra a barra necesita velas, y las velas reales solo llegan por
 * el agente de MetaTrader: las 324 operaciones de la demo son del seeder y no
 * tienen ninguna. Sin esto, el bloque más vistoso de la fase no se vería en el
 * escaparate público, que es justo donde tiene que verse.
 *
 * Lo que sale de aquí **no es mercado**: es un camino inventado que pasa por los
 * cuatro precios que sí están guardados en la operación —entrada, salida, MAE y
 * MFE— para que el reproductor enseñe algo coherente con lo que cuenta la ficha.
 * Solo se usa con el usuario de la demo; jamás con datos de un usuario real.
 *
 * El formato es exactamente el que manda el `.exe`: `symbol`, `timeframes` con
 * 1m/5m/15m/1h/4h y `markers` con la entrada y la salida. Si el agente cambia de
 * formato, esto se rompe igual que la pantalla, y es lo que se quiere.
 */
class SampleChartGenerator
{
    /** Velas de un minuto alrededor de la operación (mínimo). */
    private const BARS_1M = 360;

    /** Velas de una hora de contexto previo. */
    private const BARS_1H = 120;

    /**
     * Payload de velas de una operación, listo para subir tal cual.
     *
     * @return array{symbol: string, timeframes: array<string, list<array<string, float|int>>>, markers: list<array<string, mixed>>}
     */
    public function generate(Trade $trade, string $symbol): array
    {
        $entrada = CarbonImmutable::parse($trade->entry_time);
        $salida = CarbonImmutable::parse($trade->exit_time);

        $precioEntrada = (float) $trade->entry_price;
        $precioSalida = (float) $trade->exit_price;

        // Sin MAE/MFE guardados se usa el propio recorrido: el camino sigue siendo
        // coherente, simplemente no hay excursión que enseñar.
        $mae = (float) ($trade->mae_price ?: min($precioEntrada, $precioSalida));
        $mfe = (float) ($trade->mfe_price ?: max($precioEntrada, $precioSalida));

        // Los índices y el oro cotizan con dos decimales; los pares de divisas, con
        // cinco. El umbral separa los dos mundos sin tener que arrastrar el catálogo.
        $decimales = $precioEntrada < 10 ? 5 : 2;

        $minutos = max(1, (int) $entrada->diffInMinutes($salida));

        $finas = $this->fineCandles($entrada, $minutos, $precioEntrada, $precioSalida, $mae, $mfe, $decimales);
        $gruesas = $this->coarseCandles($salida, $precioEntrada, $precioSalida, $mae, $mfe, $decimales);

        return [
            'symbol' => $symbol,
            'timeframes' => [
                '1m' => $this->withEma($finas),
                '5m' => $this->withEma($this->aggregate($finas, 300)),
                '15m' => $this->withEma($this->aggregate($finas, 900)),
                '1h' => $this->withEma($gruesas),
                '4h' => $this->withEma($this->aggregate($gruesas, 14400)),
            ],
            'markers' => [
                [
                    'time' => $entrada->getTimestamp(),
                    'position' => 'aboveBar',
                    'color' => '#2196F3',
                    'shape' => 'arrowDown',
                    'text' => 'IN',
                    'size' => 1,
                ],
                [
                    'time' => $salida->getTimestamp(),
                    'position' => 'belowBar',
                    'color' => '#FF9800',
                    'shape' => 'arrowUp',
                    'text' => 'OUT',
                    'size' => 1,
                ],
            ],
        ];
    }

    /**
     * Velas de un minuto: la operación en el centro, con contexto a los lados.
     *
     * @return list<array<string, float|int>>
     */
    private function fineCandles(
        CarbonImmutable $entrada,
        int $minutos,
        float $precioEntrada,
        float $precioSalida,
        float $mae,
        float $mfe,
        int $decimales,
    ): array {
        $total = max(self::BARS_1M, $minutos + 80);
        $margen = intdiv($total - $minutos, 2);
        $inicio = $entrada->subMinutes($margen);

        $iEntrada = $margen;
        $iSalida = $margen + $minutos;

        // La excursión adversa y la favorable ocurren dentro de la operación, en
        // ese orden o en el contrario: las dos cosas pasan de verdad.
        $reparto = $this->twoPointsBetween($iEntrada, $iSalida);
        $primeroEsMae = mt_rand(0, 1) === 1;

        $hitos = [
            0 => $precioEntrada,
            $iEntrada => $precioEntrada,
            $reparto[0] => $primeroEsMae ? $mae : $mfe,
            $reparto[1] => $primeroEsMae ? $mfe : $mae,
            $iSalida => $precioSalida,
            $total => $precioSalida,
        ];

        return $this->walk($hitos, $total, $inicio, 60, $decimales);
    }

    /**
     * Velas de una hora: el contexto previo, terminando en el precio de salida.
     *
     * @return list<array<string, float|int>>
     */
    private function coarseCandles(
        CarbonImmutable $salida,
        float $precioEntrada,
        float $precioSalida,
        float $mae,
        float $mfe,
        int $decimales,
    ): array {
        $inicio = $salida->subHours(self::BARS_1H)->startOfHour();
        $rango = max(abs($mfe - $mae), abs($precioSalida - $precioEntrada), $precioEntrada * 0.0005);

        // El contexto arranca lejos y va llegando: la última vela cierra en la
        // salida y la penúltima zona ronda la entrada, que es lo que se ve al
        // abrir el marco de 1h en una operación de verdad.
        $hitos = [
            0 => $precioEntrada + $rango * (mt_rand(-30, 30) / 10),
            intdiv(self::BARS_1H, 3) => $precioEntrada + $rango * (mt_rand(-20, 20) / 10),
            self::BARS_1H - 2 => $precioEntrada,
            self::BARS_1H => $precioSalida,
        ];

        return $this->walk($hitos, self::BARS_1H, $inicio, 3600, $decimales);
    }

    /**
     * Camino de precios que pasa exactamente por cada hito, con ruido entre ellos.
     *
     * El cierre de cada vela es el punto del camino, y la apertura es el cierre de
     * la anterior: así no hay huecos. El ruido se recorta para que nunca supere el
     * hito más extremo — si una vela cualquiera se pasara del MAE, el reproductor
     * marcaría la excursión adversa en el minuto equivocado.
     *
     * @param  array<int, float>  $hitos  índice de vela => precio exacto
     * @return list<array<string, float|int>>
     */
    private function walk(array $hitos, int $total, CarbonImmutable $inicio, int $segundos, int $decimales): array
    {
        ksort($hitos);
        $indices = array_keys($hitos);

        $techo = max($hitos);
        $suelo = min($hitos);
        $banda = max($techo - $suelo, 10 ** -$decimales);

        $camino = [];
        for ($i = 0, $tramo = 0; $i <= $total; $i++) {
            while ($tramo < count($indices) - 2 && $i > $indices[$tramo + 1]) {
                $tramo++;
            }

            $desde = $indices[$tramo];
            $hasta = $indices[$tramo + 1];
            $paso = $hasta === $desde ? 1.0 : ($i - $desde) / ($hasta - $desde);

            $recta = $hitos[$desde] + ($hitos[$hasta] - $hitos[$desde]) * $paso;

            if (isset($hitos[$i])) {
                $camino[$i] = $hitos[$i];

                continue;
            }

            $ruido = $banda * (mt_rand(-100, 100) / 1000);
            // Se deja un 4 % de margen contra cada extremo para que solo el hito
            // toque el precio del MAE y el del MFE.
            $camino[$i] = min($techo - $banda * 0.04, max($suelo + $banda * 0.04, $recta + $ruido));
        }

        $velas = [];
        for ($i = 1; $i <= $total; $i++) {
            $apertura = round($camino[$i - 1], $decimales);
            $cierre = round($camino[$i], $decimales);
            $mecha = $banda * (mt_rand(5, 40) / 1000);

            $velas[] = [
                'time' => $inicio->addSeconds($i * $segundos)->getTimestamp(),
                'open' => $apertura,
                'high' => round(max($apertura, $cierre) + $mecha, $decimales),
                'low' => round(min($apertura, $cierre) - $mecha, $decimales),
                'close' => $cierre,
                'volume' => mt_rand(18, 140),
            ];
        }

        return $velas;
    }

    /**
     * Agrupa velas en marcos mayores.
     *
     * @param  list<array<string, float|int>>  $velas
     * @return list<array<string, float|int>>
     */
    private function aggregate(array $velas, int $segundos): array
    {
        $grupos = [];

        foreach ($velas as $vela) {
            $clave = intdiv((int) $vela['time'], $segundos) * $segundos;

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'time' => $clave,
                    'open' => $vela['open'],
                    'high' => $vela['high'],
                    'low' => $vela['low'],
                    'close' => $vela['close'],
                    'volume' => $vela['volume'],
                ];

                continue;
            }

            $grupos[$clave]['high'] = max($grupos[$clave]['high'], $vela['high']);
            $grupos[$clave]['low'] = min($grupos[$clave]['low'], $vela['low']);
            $grupos[$clave]['close'] = $vela['close'];
            $grupos[$clave]['volume'] += $vela['volume'];
        }

        ksort($grupos);

        return array_values($grupos);
    }

    /**
     * Añade la EMA de 50 que el agente manda con cada vela.
     *
     * Arranca en el primer cierre en vez de esperar cincuenta velas, igual que
     * hace el `.exe`: si no, el marco de 4h saldría sin línea entera.
     *
     * @param  list<array<string, float|int>>  $velas
     * @return list<array<string, float|int>>
     */
    private function withEma(array $velas): array
    {
        $k = 2 / 51;
        $ema = null;

        foreach ($velas as $i => $vela) {
            $ema = $ema === null
                ? (float) $vela['close']
                : (float) $vela['close'] * $k + $ema * (1 - $k);

            $velas[$i]['ema'] = $ema;
        }

        return $velas;
    }

    /**
     * Dos índices distintos y ordenados dentro del tramo abierto (desde, hasta).
     *
     * @return array{0: int, 1: int}
     */
    private function twoPointsBetween(int $desde, int $hasta): array
    {
        $hueco = $hasta - $desde;

        if ($hueco < 4) {
            return [$desde + 1, max($desde + 2, $hasta - 1)];
        }

        $primero = $desde + (int) round($hueco * (mt_rand(15, 40) / 100));
        $segundo = $desde + (int) round($hueco * (mt_rand(55, 85) / 100));

        return [$primero, max($primero + 1, $segundo)];
    }
}
