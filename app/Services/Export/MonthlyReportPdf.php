<?php

declare(strict_types=1);

namespace App\Services\Export;

use App\Support\Demo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * El informe mensual, convertido en PDF con marca (Fase 5 · P10).
 *
 * Se genera dentro de la petición, no en cola: hoy no hay ningún worker
 * garantizado en producción, y un informe que se encola y no llega nunca es peor
 * que un botón que tarda tres segundos.
 *
 * El motor es dompdf, PHP puro. Eso impone el diseño de la plantilla: tablas en
 * lugar de flex o grid, CSS en línea y nada de Tailwind. A cambio no hay que
 * instalar Chromium en el servidor para imprimir un folio.
 */
final class MonthlyReportPdf
{
    /** El logotipo, en base64, para no depender de rutas ni de chroot. */
    private const LOGO = 'img/logo_trader_h.png';

    private static ?string $logoCache = null;

    /** @param  array<string, mixed>  $report  la salida de BuildMonthlyReport */
    public function download(array $report): StreamedResponse
    {
        $binary = $this->raw($report);
        $filename = $this->filename($report);

        // Livewire solo sabe descargar StreamedResponse o BinaryFileResponse, y
        // el helper de dompdf devuelve una Response normal.
        return response()->streamDownload(
            fn () => print ($binary),
            $filename,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'no-store, no-cache',
            ]
        );
    }

    /**
     * El binario del PDF.
     *
     * El subsetting de fuentes viene apagado en la configuración del paquete, y
     * sin él dompdf incrusta DejaVu entera: el mismo informe pasa de 90 KB a casi
     * un mega. Se enciende aquí para no publicar el config solo por esto.
     *
     * @param  array<string, mixed>  $report
     */
    public function raw(array $report): string
    {
        return Pdf::setOption('enable_font_subsetting', true)
            ->loadView('exports.monthly-report', [
                'report' => $report,
                'logo' => $this->logo(),
                'isDemo' => Demo::active(),
            ])
            ->setPaper('a4')
            ->output();
    }

    /** @param  array<string, mixed>  $report */
    public function filename(array $report): string
    {
        $cuenta = $report['account']['name'] ?? __('export.pdf.all_accounts');
        $mes = $report['month']['start'] ?? now()->toDateString();

        return 'tradeforge-' . __('export.pdf.slug')
            . '-' . Str::slug((string) $cuenta)
            . '-' . substr((string) $mes, 0, 7)
            . '.pdf';
    }

    /**
     * dompdf no sabe de `asset()` ni de webp: el logotipo viaja incrustado en el
     * propio HTML como data URI, que es el único formato que no depende de dónde
     * esté servida la aplicación.
     */
    private function logo(): ?string
    {
        if (self::$logoCache !== null) {
            return self::$logoCache;
        }

        $path = public_path(self::LOGO);

        if (!is_file($path)) {
            return null; // el informe se imprime igual, solo que sin marca
        }

        return self::$logoCache = 'data:image/png;base64,' . base64_encode((string) file_get_contents($path));
    }
}
