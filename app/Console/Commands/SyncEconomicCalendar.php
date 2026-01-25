<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EconomicEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncEconomicCalendar extends Command
{
    protected $signature = 'calendar:sync'; // El nombre para ejecutarlo manualmente
    protected $description = 'Sincroniza el calendario económico desde ForexFactory';

    public function handle()
    {
        $this->info('Iniciando sincronización con ForexFactory...');

        $url = "https://nfs.faireconomy.media/ff_calendar_thisweek.xml";

        try {
            // Tu misma lógica HTTP Anti-Bloqueo
            $response = Http::withoutVerifying()
                ->withOptions(["verify" => false])
                ->withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Referer'         => 'https://www.forexfactory.com/', // Vital para saltar protecciones
                    'Origin'          => 'https://www.forexfactory.com',
                    'Connection'      => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                    'Cache-Control'   => 'no-cache',
                ])
                ->timeout(30)
                ->get($url);

            if ($response->failed()) {
                $this->error('Error HTTP al conectar.');
                return;
            }

            $xml = @simplexml_load_string($response->body());
            if (!$xml) {
                $this->error('XML inválido.');
                return;
            }

            $majors = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'NZD'];
            $count = 0;

            foreach ($xml->event as $item) {
                $currency = (string)$item->country;
                if (!in_array($currency, $majors)) continue;

                // Tu lógica de parseo de fechas exacta
                $dateString = (string)$item->date;
                $timeString = (string)$item->time;

                try {
                    if (str_contains($timeString, 'Day')) {
                        $dt = Carbon::createFromFormat('m-d-Y', $dateString)->startOfDay();
                    } else {
                        $dt = Carbon::createFromFormat('m-d-Y g:ia', $dateString . ' ' . $timeString);
                    }
                } catch (\Exception $e) {
                    continue;
                }

                $impactStr = strtolower((string)$item->impact);
                $impactMap = ['high' => 'high', 'medium' => 'medium', 'low' => 'low'];
                $impact = $impactMap[$impactStr] ?? 'low';

                // GUARDADO SIN USER_ID
                EconomicEvent::updateOrCreate(
                    [
                        // Claves para identificar si ya existe (Unique Index)
                        'date'     => $dt->format('Y-m-d'),
                        'time'     => $dt->format('H:i:s'),
                        'currency' => $currency,
                        'event'    => (string)$item->title,
                    ],
                    [
                        'impact'   => $impact,
                        'forecast' => (string)$item->forecast,
                        'previous' => (string)$item->previous,
                        // Al correrse diario, esto actualizará el dato real cuando salga
                        'actual'   => (string)$item->forecast == '' ? null : (string)$item->forecast // Ojo: el XML a veces trae el actual
                    ]
                );
                $count++;
            }

            $this->info("Sincronización completada: $count eventos procesados.");
            Log::info("Cron Calendario: $count eventos.");
        } catch (\Exception $e) {
            $this->error('Excepción: ' . $e->getMessage());
            Log::error('Cron Calendario Error: ' . $e->getMessage());
        }
    }
}
