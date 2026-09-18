<?php

declare(strict_types=1);

namespace App\Actions\Export;

use App\Models\Trade;
use App\Support\Demo;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Las operaciones filtradas, en un CSV que se abre en Excel sin pelearse (P10).
 *
 * Lo que baja es exactamente lo que el usuario tiene delante en /trades: la misma
 * query con los mismos filtros. Si el fichero trajera más filas que la pantalla,
 * el usuario tendría que decidir cuál de las dos le miente.
 *
 * Va en lotes y por streaming a propósito. Un histórico de prop firm son miles de
 * operaciones y cargarlas enteras en memoria para construir una cadena gigante es
 * la forma más rápida de convertir una descarga en un error 500.
 */
final class ExportTradesCsv
{
    /** Operaciones por lote. Ni tan pocas que sean mil consultas, ni tantas que pesen. */
    private const CHUNK = 500;

    /** BOM UTF-8: sin él, Excel se come los acentos de las notas. */
    private const BOM = "\xEF\xBB\xBF";

    /** Orden de las columnas. La clave de idioma es `export.columns.<esto>`. */
    private const COLUMNS = [
        'ticket',
        'account',
        'asset',
        'direction',
        'entry_time',
        'exit_time',
        'entry_price',
        'exit_price',
        'size',
        'pnl',
        'pnl_percentage',
        'duration',
        'strategy',
        'mistakes',
        'mood',
        'notes',
    ];

    /**
     * @param  Builder  $query  la query que pinta la pantalla, con sus filtros ya puestos
     */
    public function response(Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            fn () => $this->write($query),
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                // Sin esto algunos proxies cachean la descarga y el siguiente
                // filtro devuelve el CSV anterior.
                'Cache-Control' => 'no-store, no-cache',
            ]
        );
    }

    /** Nombre del fichero: `tradeforge-operaciones-2026-08-27.csv`. */
    public function filename(): string
    {
        return 'tradeforge-' . __('export.csv.slug') . '-' . now()->format('Y-m-d') . '.csv';
    }

    private function write(Builder $query): void
    {
        $out = fopen('php://output', 'w');
        fwrite($out, self::BOM);

        // La demo son datos sembrados de otro usuario. El fichero lo dice en la
        // primera línea para que nadie lo confunda con su propio histórico.
        if (Demo::active()) {
            $this->put($out, [__('export.demo_notice')]);
        }

        $this->put($out, array_map(fn (string $c): string => __("export.columns.{$c}"), self::COLUMNS));

        // Desempate por id: `lazy()` pagina por offset y `exit_time` se repite
        // entre operaciones de la misma vela, así que sin un orden total alguna
        // fila se colaría dos veces o no saldría.
        (clone $query)
            ->with(['account:id,name', 'tradeAsset:id,symbol', 'strategy:id,name', 'mistakes'])
            ->orderBy('trades.id', 'desc')
            ->lazy(self::CHUNK)
            ->each(fn (Trade $trade) => $this->put($out, $this->row($trade)));

        fclose($out);
    }

    /** @return array<int, string> */
    private function row(Trade $trade): array
    {
        return [
            (string) ($trade->ticket ?? ''),
            (string) ($trade->account?->name ?? ''),
            (string) ($trade->tradeAsset?->symbol ?? ''),
            __('export.direction.' . $trade->direction),
            $trade->entry_time?->format('Y-m-d H:i') ?? '',
            $trade->exit_time?->format('Y-m-d H:i') ?? '',
            $this->number($trade->entry_price, 5),
            $this->number($trade->exit_price, 5),
            $this->number($trade->size, 2),
            $this->number($trade->pnl, 2),
            $trade->pnl_percentage === null ? '' : $this->number($trade->pnl_percentage, 2),
            (string) ($trade->duration_minutes ?? ''),
            (string) ($trade->strategy?->name ?? ''),
            $trade->mistakes->map(fn ($m): string => (string) $m->display_name)->implode(' | '),
            (string) ($trade->mood ?? ''),
            // Las notas llevan saltos de línea. Dentro de una celda son legales,
            // pero rompen cualquier lectura del fichero línea a línea.
            trim(preg_replace('/\s+/u', ' ', (string) ($trade->notes ?? '')) ?? ''),
        ];
    }

    /**
     * Excel usa el separador de lista del sistema, y en España es `;`: un CSV con
     * comas se abre entero dentro de la primera columna. Se elige por idioma en
     * lugar de fijar uno, junto con el separador decimal, para que el fichero
     * cuadre con el Excel de quien lo descarga.
     */
    private function delimiter(): string
    {
        return app()->getLocale() === 'es' ? ';' : ',';
    }

    private function number(mixed $value, int $decimals): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format(
            (float) $value,
            $decimals,
            app()->getLocale() === 'es' ? ',' : '.',
            '' // sin separador de miles: es un número para una máquina, no un titular
        );
    }

    /** @param  array<int, string>  $row */
    private function put($handle, array $row): void
    {
        fputcsv($handle, $row, $this->delimiter(), '"', '');
    }
}
