<?php

declare(strict_types=1);

namespace App\Services\Import;

/**
 * Catálogo de plataformas soportadas por el importador.
 *
 * Cada preset es un diccionario de alias de cabecera más las manías de esa
 * plataforma (cómo escribe la dirección, en qué unidad va el volumen). Los
 * nombres exactos de columna cambian entre versiones y brokers, así que el
 * emparejamiento es por alias y siempre corregible a mano en la pantalla de
 * mapeo: el preset acierta el 90 % y el usuario arregla el resto.
 */
final class ImportPreset
{
    /**
     * Campos canónicos a los que se mapea cualquier fichero.
     *
     * `required` marca lo que no se puede deducir de ninguna otra columna.
     */
    public const FIELDS = [
        'symbol' => ['required' => true],
        'direction' => ['required' => true],
        'entry_time' => ['required' => true],
        'exit_time' => ['required' => true],
        'entry_price' => ['required' => true],
        'exit_price' => ['required' => true],
        'size' => ['required' => true],
        'pnl' => ['required' => true],
        'ticket' => ['required' => false],
        'position_id' => ['required' => false],
        'commission' => ['required' => false],
        'swap' => ['required' => false],
        'mae_price' => ['required' => false],
        'mfe_price' => ['required' => false],
        'notes' => ['required' => false],
    ];

    /**
     * @param  array<string, array<int, string>>  $aliases  campo canónico => alias de cabecera
     * @param  array<int, string>  $signature  cabeceras que delatan la plataforma
     * @param  array<string, array<int, string>>  $directionValues  'long'|'short' => valores del fichero
     */
    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $aliases,
        public readonly array $signature = [],
        public readonly array $directionValues = [],
        public readonly float $volumeDivisor = 1.0,
    ) {}

    /** @return array<string, self> */
    public static function all(): array
    {
        return [
            'metatrader' => new self(
                key: 'metatrader',
                label: 'MetaTrader 4 / 5',
                aliases: [
                    'ticket' => ['ticket', 'deal', 'orden', 'order', 'operación', 'operacion'],
                    'position_id' => ['position', 'posición', 'posicion', 'position id'],
                    'symbol' => ['symbol', 'símbolo', 'simbolo', 'item', 'instrumento'],
                    'direction' => ['type', 'tipo', 'direction'],
                    'size' => ['volume', 'volumen', 'size', 'lots', 'lotes'],
                    'entry_price' => ['open price', 'precio de apertura', 'price open', 'entry price'],
                    'exit_price' => ['close price', 'precio de cierre', 'price close', 'exit price'],
                    'entry_time' => ['open time', 'hora de apertura', 'time open', 'entry time'],
                    'exit_time' => ['close time', 'hora de cierre', 'time close', 'exit time'],
                    'pnl' => ['profit', 'beneficio', 'p/l', 'pnl'],
                    'commission' => ['commission', 'comisión', 'comision'],
                    'swap' => ['swap', 'rollover'],
                    'notes' => ['comment', 'comentario'],
                ],
                signature: ['swap', 'profit', 'symbol'],
                directionValues: ['long' => ['buy', 'compra', '0'], 'short' => ['sell', 'venta', '1']],
            ),

            'ctrader' => new self(
                key: 'ctrader',
                label: 'cTrader',
                aliases: [
                    'ticket' => ['order id', 'deal id', 'id'],
                    'position_id' => ['position id', 'positionid'],
                    'symbol' => ['symbol', 'instrument'],
                    'direction' => ['direction', 'side', 'type'],
                    'size' => ['volume', 'quantity', 'lots', 'closing quantity'],
                    'entry_price' => ['entry price', 'opening price', 'open price'],
                    'exit_price' => ['closing price', 'close price', 'exit price'],
                    'entry_time' => ['entry time', 'opening time', 'open time'],
                    'exit_time' => ['closing time', 'close time', 'exit time'],
                    'pnl' => ['net usd', 'net profit', 'gross profit', 'p/l', 'profit'],
                    'commission' => ['commission', 'commissions'],
                    'swap' => ['swap', 'swap usd'],
                    'notes' => ['label', 'comment'],
                ],
                signature: ['position id', 'closing price'],
                directionValues: ['long' => ['buy', 'long'], 'short' => ['sell', 'short']],
            ),

            'dxtrade' => new self(
                key: 'dxtrade',
                label: 'DXtrade',
                aliases: [
                    'ticket' => ['order id', 'trade id', 'id'],
                    'position_id' => ['position id', 'position'],
                    'symbol' => ['symbol', 'instrument'],
                    'direction' => ['side', 'direction', 'type'],
                    'size' => ['quantity', 'qty', 'volume', 'size'],
                    'entry_price' => ['open price', 'entry price', 'avg open price'],
                    'exit_price' => ['close price', 'exit price', 'avg close price'],
                    'entry_time' => ['open time', 'opened', 'entry time', 'open date'],
                    'exit_time' => ['close time', 'closed', 'exit time', 'close date'],
                    'pnl' => ['p/l', 'pnl', 'profit', 'realized p/l', 'net p/l'],
                    'commission' => ['commission', 'fees', 'fee'],
                    'swap' => ['swap', 'financing', 'rollover'],
                    'notes' => ['comment', 'note'],
                ],
                signature: ['realized p/l', 'instrument'],
                directionValues: ['long' => ['buy', 'long', 'b'], 'short' => ['sell', 'short', 's']],
            ),

            'matchtrader' => new self(
                key: 'matchtrader',
                label: 'Match-Trader',
                aliases: [
                    'ticket' => ['id', 'order id', 'trade id'],
                    'position_id' => ['position id', 'position'],
                    'symbol' => ['symbol', 'instrument'],
                    'direction' => ['side', 'direction', 'type'],
                    'size' => ['volume', 'lots', 'size', 'quantity'],
                    'entry_price' => ['open price', 'opening price', 'entry price'],
                    'exit_price' => ['close price', 'closing price', 'exit price'],
                    'entry_time' => ['open time', 'opening time', 'entry time'],
                    'exit_time' => ['close time', 'closing time', 'exit time'],
                    'pnl' => ['profit', 'net profit', 'p/l', 'gross profit'],
                    'commission' => ['commission', 'commissions'],
                    'swap' => ['swap', 'swaps'],
                    'notes' => ['comment', 'note'],
                ],
                signature: ['login', 'swaps'],
                directionValues: ['long' => ['buy', 'long'], 'short' => ['sell', 'short']],
            ),

            'generic' => new self(
                key: 'generic',
                label: 'CSV genérico',
                aliases: [
                    'ticket' => ['ticket', 'id', 'trade id', 'order id', 'deal'],
                    'position_id' => ['position id', 'position', 'posición', 'posicion'],
                    'symbol' => ['symbol', 'símbolo', 'simbolo', 'instrument', 'instrumento', 'pair', 'par', 'activo'],
                    'direction' => ['direction', 'dirección', 'direccion', 'side', 'type', 'tipo'],
                    'size' => ['size', 'volume', 'volumen', 'lots', 'lotes', 'quantity', 'cantidad', 'qty'],
                    'entry_price' => ['entry price', 'open price', 'precio entrada', 'precio de entrada', 'precio apertura'],
                    'exit_price' => ['exit price', 'close price', 'precio salida', 'precio de salida', 'precio cierre'],
                    'entry_time' => ['entry time', 'open time', 'fecha entrada', 'hora entrada', 'fecha de apertura', 'opened'],
                    'exit_time' => ['exit time', 'close time', 'fecha salida', 'hora salida', 'fecha de cierre', 'closed'],
                    'pnl' => ['pnl', 'p&l', 'p/l', 'profit', 'beneficio', 'resultado', 'net'],
                    'commission' => ['commission', 'comisión', 'comision', 'fees', 'fee'],
                    'swap' => ['swap', 'rollover', 'financing'],
                    'mae_price' => ['mae', 'mae price', 'max adverse', 'peor precio'],
                    'mfe_price' => ['mfe', 'mfe price', 'max favorable', 'mejor precio'],
                    'notes' => ['notes', 'notas', 'comment', 'comentario', 'label'],
                ],
                directionValues: [
                    'long' => ['long', 'buy', 'compra', 'largo', 'l', 'b', '0'],
                    'short' => ['short', 'sell', 'venta', 'corto', 's', '1'],
                ],
            ),
        ];
    }

    public static function find(string $key): self
    {
        return self::all()[$key] ?? self::all()['generic'];
    }

    /**
     * Elige el preset que mejor encaja con las cabeceras del fichero.
     *
     * Puntúa cuántos alias reconoce cada preset y premia las cabeceras firma.
     * Si nada destaca, devuelve el genérico, que reconoce el vocabulario común.
     *
     * @param  array<int, string>  $headers
     */
    public static function detect(array $headers): self
    {
        $normalized = array_map(self::normalize(...), $headers);
        $best = self::all()['generic'];
        $bestScore = 0;

        foreach (self::all() as $preset) {
            if ($preset->key === 'generic') {
                continue;
            }

            $score = 0;

            foreach ($preset->aliases as $candidates) {
                foreach ($candidates as $candidate) {
                    if (in_array(self::normalize($candidate), $normalized, true)) {
                        $score++;
                        break;
                    }
                }
            }

            foreach ($preset->signature as $signature) {
                if (in_array(self::normalize($signature), $normalized, true)) {
                    $score += 3;
                }
            }

            if ($score > $bestScore) {
                $best = $preset;
                $bestScore = $score;
            }
        }

        // Menos de la mitad de los campos reconocidos no es una detección: es ruido.
        return $bestScore >= 8 ? $best : self::all()['generic'];
    }

    /**
     * Propone el mapeo cabecera → campo canónico.
     *
     * Se resuelve campo a campo y en el orden de FIELDS, y cada cabecera se
     * consume una sola vez: si no, en un informe de MetaTrader las dos columnas
     * llamadas «Price» acabarían las dos en `entry_price`.
     *
     * @param  array<int, string>  $headers
     * @return array<string, string|null> campo canónico => cabecera
     */
    public function guessMapping(array $headers): array
    {
        $mapping = [];
        $taken = [];

        foreach (array_keys(self::FIELDS) as $field) {
            $mapping[$field] = null;

            foreach ($this->aliases[$field] ?? [] as $alias) {
                foreach ($headers as $header) {
                    if (in_array($header, $taken, true)) {
                        continue;
                    }

                    if (self::normalize($header) === self::normalize($alias)) {
                        $mapping[$field] = $header;
                        $taken[] = $header;

                        continue 3;
                    }
                }
            }
        }

        return $mapping;
    }

    /** Compara cabeceras ignorando mayúsculas, acentos, espacios y puntuación. */
    public static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);

        return (string) preg_replace('/[^a-z0-9]+/', '', $value);
    }
}
