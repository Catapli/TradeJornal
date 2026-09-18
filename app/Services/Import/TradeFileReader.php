<?php

declare(strict_types=1);

namespace App\Services\Import;

use RuntimeException;

/**
 * Lee el fichero que sube el usuario y lo deja en cabeceras + filas.
 *
 * Soporta dos familias:
 *  - **CSV / TSV**: delimitador y codificación detectados a ojo (`;` es lo normal
 *    en exportaciones europeas y rompe cualquier lector que asuma la coma).
 *  - **Informe HTML de MetaTrader**: lo que exporta el propio terminal con
 *    «Informe» / «Guardar como informe». Se localiza la tabla de operaciones
 *    cerradas y se lee por posición, porque sus cabeceras están repetidas
 *    (dos «Time», dos «Price») y no sirven para mapear por nombre.
 *
 * No se añade dependencia de terceros: `fgetcsv` y `DOMDocument` bastan y evitan
 * meter una librería de Excel entera para leer cuatro columnas.
 */
class TradeFileReader
{
    /** Nunca se leen más filas que esto: un fichero corrupto no debe tumbar el proceso. */
    public const MAX_ROWS = 20000;

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>, format: string}
     */
    public function read(string $path, string $originalName): array
    {
        $extension = mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException(__('import.errors.empty_file'));
        }

        $contents = $this->toUtf8($contents);

        return in_array($extension, ['htm', 'html'], true)
            ? $this->readMetaTraderHtml($contents)
            : $this->readDelimited($contents);
    }

    // ─────────────────────────────────────────────────────────────
    // CSV / TSV
    // ─────────────────────────────────────────────────────────────

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>, format: string}
     */
    private function readDelimited(string $contents): array
    {
        $delimiter = $this->detectDelimiter($contents);

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        $headers = [];
        $rows = [];

        while (($record = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Filas en blanco: las exportaciones suelen traer varias al final.
            if ($record === [null] || $record === [] || $this->isBlank($record)) {
                continue;
            }

            if ($headers === []) {
                $headers = $this->uniqueHeaders(array_map(
                    static fn ($value) => trim((string) $value),
                    $record
                ));

                continue;
            }

            $rows[] = $this->combine($headers, $record);

            if (count($rows) >= self::MAX_ROWS) {
                break;
            }
        }

        fclose($handle);

        if ($headers === []) {
            throw new RuntimeException(__('import.errors.no_headers'));
        }

        return ['headers' => $headers, 'rows' => $rows, 'format' => 'csv'];
    }

    private function detectDelimiter(string $contents): string
    {
        $firstLine = strtok($contents, "\r\n") ?: '';
        $counts = [];

        foreach ([';', ',', "\t", '|'] as $candidate) {
            $counts[$candidate] = substr_count($firstLine, $candidate);
        }

        arsort($counts);
        $best = array_key_first($counts);

        return $counts[$best] > 0 ? $best : ',';
    }

    // ─────────────────────────────────────────────────────────────
    // Informe HTML de MetaTrader
    // ─────────────────────────────────────────────────────────────

    /**
     * MT4 y MT5 exportan tablas distintas, pero ambas con el orden de columnas fijo,
     * así que se leen por posición y se devuelven ya con cabeceras canónicas.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>, format: string}
     */
    private function readMetaTraderHtml(string $contents): array
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $contents);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $rows = [];

        foreach ($document->getElementsByTagName('tr') as $tr) {
            $cells = [];

            foreach ($tr->getElementsByTagName('td') as $td) {
                $cells[] = trim(preg_replace('/\s+/u', ' ', $td->textContent) ?? '');
            }

            $row = $this->metaTraderRow($cells);

            if ($row !== null) {
                $rows[] = $row;
            }

            if (count($rows) >= self::MAX_ROWS) {
                break;
            }
        }

        if ($rows === []) {
            throw new RuntimeException(__('import.errors.no_metatrader_rows'));
        }

        return [
            'headers' => array_keys($rows[0]),
            'rows' => $rows,
            'format' => 'metatrader_html',
        ];
    }

    /**
     * Reconoce una fila de operación cerrada dentro del informe.
     *
     * El informe mezcla cabeceras, resúmenes y balance con las operaciones, así que
     * el filtro es estructural: tipo buy/sell y dos fechas reconocibles.
     *
     * @param  array<int, string>  $cells
     * @return array<string, string>|null
     */
    private function metaTraderRow(array $cells): ?array
    {
        if (count($cells) < 10) {
            return null;
        }

        $typeIndex = null;

        foreach ($cells as $i => $cell) {
            if (in_array(mb_strtolower($cell), ['buy', 'sell', 'compra', 'venta'], true)) {
                $typeIndex = $i;
                break;
            }
        }

        if ($typeIndex === null) {
            return null;
        }

        $dates = [];

        foreach ($cells as $i => $cell) {
            if ($this->looksLikeDateTime($cell)) {
                $dates[$i] = $cell;
            }
        }

        if (count($dates) < 2) {
            return null;
        }

        $dateIndexes = array_keys($dates);
        $openIndex = $dateIndexes[0];
        $closeIndex = $dateIndexes[count($dateIndexes) - 1];

        // A la derecha de la fecha de cierre, ambos informes ponen el mismo orden:
        // precio de cierre, comisión, [impuestos en MT4], swap y beneficio.
        $tail = [];

        foreach ($cells as $i => $cell) {
            if ($i > $closeIndex && $this->looksNumeric($cell)) {
                $tail[] = $cell;
            }
        }

        $last = count($tail) - 1;

        if ($last < 0) {
            return null;
        }

        // Entre el tipo y la fecha de cierre: volumen, precio de apertura y, en MT4,
        // también el símbolo. Los S/L y T/P que vienen detrás se ignoran solos.
        $middle = array_slice($cells, $typeIndex + 1, $closeIndex - $typeIndex - 1);
        $symbol = null;
        $numbers = [];

        foreach ($middle as $cell) {
            if ($this->looksNumeric($cell)) {
                $numbers[] = $cell;
            } elseif ($symbol === null && $cell !== '') {
                $symbol = $cell;
            }
        }

        // MT5 pone el símbolo *antes* del tipo (Time, Position, Symbol, Type, …),
        // así que si no apareció a la derecha se busca a la izquierda.
        if ($symbol === null) {
            foreach (array_slice($cells, 0, $typeIndex) as $cell) {
                if ($cell !== '' && !$this->looksNumeric($cell) && !$this->looksLikeDateTime($cell)) {
                    $symbol = $cell;
                    break;
                }
            }
        }

        if ($symbol === null || count($numbers) < 2) {
            return null;
        }

        return [
            'ticket' => $this->findTicket($cells, $typeIndex),
            'entry_time' => $dates[$openIndex],
            'direction' => $cells[$typeIndex],
            'size' => $numbers[0],
            'symbol' => $symbol,
            'entry_price' => $numbers[1],
            'exit_time' => $dates[$closeIndex],
            'exit_price' => $tail[0],
            'commission' => $last >= 3 ? $tail[1] : '0',
            'swap' => $last >= 2 ? $tail[$last - 1] : '0',
            'pnl' => $tail[$last],
        ];
    }

    /**
     * El número de operación es el primer entero puro que hay antes del tipo:
     * en MT4 es la primera celda y en MT5 la segunda, detrás de la fecha.
     *
     * @param  array<int, string>  $cells
     */
    private function findTicket(array $cells, int $typeIndex): string
    {
        foreach (array_slice($cells, 0, $typeIndex) as $cell) {
            if (preg_match('/^\d{3,}$/', $cell)) {
                return $cell;
            }
        }

        return '';
    }

    private function looksLikeDateTime(string $value): bool
    {
        return (bool) preg_match('/^\d{4}[.\-\/]\d{2}[.\-\/]\d{2}[ T]\d{2}:\d{2}/', $value);
    }

    // ─────────────────────────────────────────────────────────────
    // Utilidades
    // ─────────────────────────────────────────────────────────────

    /** MetaTrader exporta en UTF-16 con frecuencia; los brokers europeos en Windows-1252. */
    private function toUtf8(string $contents): string
    {
        if (str_starts_with($contents, "\xFF\xFE") || str_starts_with($contents, "\xFE\xFF")) {
            return (string) mb_convert_encoding($contents, 'UTF-8', 'UTF-16');
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        if (!mb_check_encoding($contents, 'UTF-8')) {
            return (string) mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        return $contents;
    }

    /**
     * Desambigua cabeceras repetidas («Price», «Price» → «Price», «Price (2)»).
     *
     * @param  array<int, string>  $headers
     * @return array<int, string>
     */
    private function uniqueHeaders(array $headers): array
    {
        $seen = [];
        $result = [];

        foreach ($headers as $i => $header) {
            $header = $header !== '' ? $header : 'Columna ' . ($i + 1);
            $key = mb_strtolower($header);

            if (isset($seen[$key])) {
                $seen[$key]++;
                $header .= ' (' . $seen[$key] . ')';
            } else {
                $seen[$key] = 1;
            }

            $result[] = $header;
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $record
     * @return array<string, string>
     */
    private function combine(array $headers, array $record): array
    {
        $row = [];

        foreach ($headers as $i => $header) {
            $row[$header] = trim((string) ($record[$i] ?? ''));
        }

        return $row;
    }

    /** @param array<int, string|null> $record */
    private function isBlank(array $record): bool
    {
        foreach ($record as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function looksNumeric(string $value): bool
    {
        return (bool) preg_match('/^-?[\d.,\s]+$/', $value) && preg_match('/\d/', $value);
    }
}
