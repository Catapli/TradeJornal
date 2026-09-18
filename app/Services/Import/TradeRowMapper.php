<?php

declare(strict_types=1);

namespace App\Services\Import;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Convierte una fila cruda del fichero en los campos que espera `trades`.
 *
 * Aquí vive toda la suciedad del mundo real: separadores decimales europeos,
 * miles con punto, importes entre paréntesis para los negativos, símbolos de
 * divisa pegados, «Buy»/«Compra»/«0» para la misma dirección y ocho formatos de
 * fecha distintos. Si algo no se puede interpretar, se devuelve un error legible
 * con el nombre del campo en vez de dejar que reviente contra la base de datos.
 */
class TradeRowMapper
{
    /** Formatos que se prueban en orden antes de rendirse con una fecha. */
    private const DATE_FORMATS = [
        'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d',
        'Y.m.d H:i:s', 'Y.m.d H:i', 'Y.m.d',
        'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
        'm/d/Y H:i:s', 'm/d/Y H:i', 'm/d/Y',
        'd.m.Y H:i:s', 'd.m.Y H:i', 'd.m.Y',
        'd-m-Y H:i:s', 'd-m-Y H:i',
    ];

    public function __construct(
        private readonly ImportPreset $preset,
        /** @var array<string, string|null> campo canónico => cabecera del fichero */
        private readonly array $mapping,
        /** Si el P&L del fichero es bruto, se le restan comisión y swap. */
        private readonly bool $pnlIncludesFees = true,
        /** Interpretación forzada del separador decimal: 'auto', 'dot' o 'comma'. */
        private readonly string $decimalMode = 'auto',
    ) {}

    /**
     * @param  array<string, string>  $row
     * @return array{data: array<string, mixed>|null, errors: array<int, string>}
     */
    public function map(array $row): array
    {
        $errors = [];

        $symbol = $this->raw($row, 'symbol');
        if ($symbol === '') {
            $errors[] = __('import.errors.field_missing', ['field' => __('import.fields.symbol')]);
        }

        $direction = $this->direction($this->raw($row, 'direction'));
        if ($direction === null) {
            $errors[] = __('import.errors.bad_direction', ['value' => $this->raw($row, 'direction')]);
        }

        $entryTime = $this->date($this->raw($row, 'entry_time'));
        if ($entryTime === null) {
            $errors[] = __('import.errors.bad_date', ['field' => __('import.fields.entry_time'), 'value' => $this->raw($row, 'entry_time')]);
        }

        $exitTime = $this->date($this->raw($row, 'exit_time'));
        if ($exitTime === null) {
            $errors[] = __('import.errors.bad_date', ['field' => __('import.fields.exit_time'), 'value' => $this->raw($row, 'exit_time')]);
        }

        $entryPrice = $this->number($this->raw($row, 'entry_price'));
        $exitPrice = $this->number($this->raw($row, 'exit_price'));
        $size = $this->number($this->raw($row, 'size'));
        $pnl = $this->number($this->raw($row, 'pnl'));

        foreach (['entry_price' => $entryPrice, 'exit_price' => $exitPrice, 'size' => $size, 'pnl' => $pnl] as $field => $value) {
            if ($value === null) {
                $errors[] = __('import.errors.bad_number', ['field' => __('import.fields.' . $field), 'value' => $this->raw($row, $field)]);
            }
        }

        if ($errors !== []) {
            return ['data' => null, 'errors' => $errors];
        }

        /** @var CarbonImmutable $entryTime */
        /** @var CarbonImmutable $exitTime */
        if ($exitTime->lessThan($entryTime)) {
            return ['data' => null, 'errors' => [__('import.errors.exit_before_entry')]];
        }

        $commission = $this->number($this->raw($row, 'commission')) ?? 0.0;
        $swap = $this->number($this->raw($row, 'swap')) ?? 0.0;

        // Si el fichero trae el beneficio bruto, el neto es lo que de verdad entró
        // en la cuenta. Se suman con el signo que traigan: MetaTrader y cTrader
        // exportan la comisión en negativo, y el swap puede ir en cualquiera de los
        // dos sentidos. Quien exporte la comisión en positivo deja marcada la
        // casilla «el P&L ya incluye comisiones» y esto no se toca.
        $netPnl = $this->pnlIncludesFees
            ? $pnl
            : $pnl + $commission + $swap;

        $size = abs((float) $size) / $this->preset->volumeDivisor;

        return [
            'data' => [
                'symbol' => mb_strtoupper(trim($symbol)),
                'direction' => $direction,
                'entry_time' => $entryTime,
                'exit_time' => $exitTime,
                'entry_price' => (float) $entryPrice,
                'exit_price' => (float) $exitPrice,
                'size' => round($size, 2),
                'pnl' => round((float) $netPnl, 2),
                // Carbon 3 devuelve float en diffInMinutes y la columna es entera.
                'duration_minutes' => max(0, (int) round(abs($entryTime->diffInMinutes($exitTime)))),
                'ticket' => $this->nullable($this->raw($row, 'ticket')),
                'position_id' => $this->nullable($this->raw($row, 'position_id')),
                'mae_price' => $this->number($this->raw($row, 'mae_price')),
                'mfe_price' => $this->number($this->raw($row, 'mfe_price')),
                'notes' => $this->nullable($this->raw($row, 'notes')),
            ],
            'errors' => [],
        ];
    }

    /** @param array<string, string> $row */
    private function raw(array $row, string $field): string
    {
        $header = $this->mapping[$field] ?? null;

        return $header === null ? '' : trim((string) ($row[$header] ?? ''));
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : mb_substr($value, 0, 190);
    }

    private function direction(string $value): ?string
    {
        $normalized = ImportPreset::normalize($value);

        if ($normalized === '') {
            return null;
        }

        foreach ($this->preset->directionValues as $direction => $candidates) {
            foreach ($candidates as $candidate) {
                if ($normalized === ImportPreset::normalize($candidate)) {
                    return $direction;
                }
            }
        }

        // Muchos brokers escriben «Buy Limit», «Sell Stop» o «buy_market».
        if (str_contains($normalized, 'buy') || str_contains($normalized, 'long') || str_contains($normalized, 'compra')) {
            return 'long';
        }

        if (str_contains($normalized, 'sell') || str_contains($normalized, 'short') || str_contains($normalized, 'venta')) {
            return 'short';
        }

        return null;
    }

    /**
     * Interpreta un importe con separadores mixtos.
     *
     * En modo automático la regla es la última posición: si la coma va detrás del
     * punto, la coma es el decimal («1.234,56»); si no, lo es el punto.
     */
    private function number(string $value): ?float
    {
        if ($value === '') {
            return null;
        }

        $negative = str_starts_with($value, '(') && str_ends_with($value, ')');
        $clean = preg_replace('/[^\d,.\-]/u', '', $value) ?? '';

        if ($clean === '' || $clean === '-') {
            return null;
        }

        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');

        $commaIsDecimal = match ($this->decimalMode) {
            'comma' => true,
            'dot' => false,
            default => $lastComma !== false && ($lastDot === false || $lastComma > $lastDot),
        };

        $clean = $commaIsDecimal
            ? str_replace(',', '.', str_replace('.', '', $clean))
            : str_replace(',', '', $clean);

        if (!is_numeric($clean)) {
            return null;
        }

        $number = (float) $clean;

        return $negative ? -abs($number) : $number;
    }

    private function date(string $value): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }

        // MetaTrader escribe 2026.03.14 09:15:00; normalizar el separador evita
        // que Carbon lo lea como m/d/Y en algunos locales.
        $candidate = trim($value);

        // DateTimeImmutable en vez de Carbon: en modo estricto Carbon lanza
        // InvalidFormatException en lugar de devolver false, y aquí probar
        // formatos hasta acertar es justamente el algoritmo.
        foreach (self::DATE_FORMATS as $format) {
            $parsed = \DateTimeImmutable::createFromFormat($format, $candidate);

            if ($parsed !== false && $parsed->format($format) === $candidate) {
                return CarbonImmutable::instance($parsed);
            }
        }

        try {
            return CarbonImmutable::parse($candidate);
        } catch (Throwable) {
            return null;
        }
    }
}
