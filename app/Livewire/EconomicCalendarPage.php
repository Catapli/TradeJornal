<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\EconomicEvent;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EconomicCalendarPage extends Component
{
    // Filtros
    public $selectedDate;
    public $filterImpact = []; // ['high', 'medium']
    public $filterCurrency = []; // ['USD', 'EUR']

    public function mount()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->filterImpact = ['high', 'medium', 'low']; // Todos por defecto
        $this->filterCurrency = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD'];
    }

    public function syncWithApi()
    {
        // URL del Feed XML Oficial de ForexFactory
        $url = "https://nfs.faireconomy.media/ff_calendar_thisweek.xml";

        try {
            // 1. PETICIÓN HTTP "STEALTH" (ANTIBLOQUEO)
            // Configuramos cabeceras para parecer un navegador humano y evitar el 403
            $response = Http::withoutVerifying() // Ignora SSL local/Fortigate
                ->withOptions(["verify" => false]) // Doble seguridad para cURL
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
                ->timeout(20) // Espera hasta 20s si la red es lenta
                ->get($url);

            if ($response->failed()) {
                throw new \Exception("Bloqueo detectado (HTTP " . $response->status() . "). La IP podría estar restringida.");
            }

            // 2. PARSEO DEL XML
            $xmlString = $response->body();

            // Usamos @ para suprimir warnings si el XML viene sucio
            $xml = @simplexml_load_string($xmlString);

            if (!$xml) {
                $this->dispatch('show-alert', ['type' => 'error', 'message' => 'El formato del archivo recibido no es válido.']);
                return;
            }

            // 3. PROCESADO DE DATOS
            $count = 0;
            // Filtramos solo las divisas que nos interesan
            $majors = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'NZD'];

            foreach ($xml->event as $item) {
                $currency = (string)$item->country;

                // Si no es una divisa mayor, saltamos
                if (!in_array($currency, $majors)) continue;

                // Datos de fecha y hora del XML (Ej: 01-21-2026 | 8:30am)
                $dateString = (string)$item->date;
                $timeString = (string)$item->time;

                try {
                    if (str_contains($timeString, 'Day')) {
                        // Eventos de todo el día (festivos, reuniones)
                        $dt = Carbon::createFromFormat('m-d-Y', $dateString);
                        // Ajustamos hora a 00:00:00
                        $dt->startOfDay();
                    } else {
                        // Eventos con hora específica
                        $fullString = $dateString . ' ' . $timeString;
                        // Formato: Mes-Dia-Año Hora:Minutoam/pm
                        $dt = Carbon::createFromFormat('m-d-Y g:ia', $fullString);
                    }
                } catch (\Exception $e) {
                    continue; // Si falla la fecha, ignoramos este evento
                }

                // Mapeo de Impacto (ForexFactory -> Tu Base de Datos)
                $impactStr = strtolower((string)$item->impact);
                $impactMap = [
                    'high'    => 'high',
                    'medium'  => 'medium',
                    'low'     => 'low',
                    'holiday' => 'low'
                ];
                $impact = $impactMap[$impactStr] ?? 'low';

                // 4. GUARDADO EN BASE DE DATOS
                // Usamos updateOrCreate para evitar duplicados si sincronizas varias veces
                EconomicEvent::updateOrCreate(
                    [
                        'user_id'  => Auth::id(),
                        'event'    => (string)$item->title,
                        'date'     => $dt->format('Y-m-d'), // Clave única compuesta: Usuario + Evento + Fecha + Divisa
                        'currency' => $currency,
                    ],
                    [
                        'time'     => $dt->format('H:i:s'),
                        'impact'   => $impact,
                        'forecast' => (string)$item->forecast,
                        'previous' => (string)$item->previous,
                        'actual'   => null // El XML gratuito no suele traer el "Actual" en tiempo real
                    ]
                );
                $count++;
            }

            // 5. FINALIZACIÓN
            $this->dispatch('show-alert', ['type' => 'success', 'message' => "Sincronizados {$count} eventos correctamente."]);

            // Limpiamos filtros para que el usuario vea los datos nuevos
            $this->filterImpact = [];
            $this->filterCurrency = [];
        } catch (\Exception $e) {
            Log::error('Sync Error: ' . $e->getMessage());
            $this->dispatch('show-alert', ['type' => 'error', 'message' => 'Error: ' . substr($e->getMessage(), 0, 80)]);
        }
    }
    // Navegación Fechas
    public function prevDay()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
    }
    public function nextDay()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
    }
    public function setToday()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
    }

    public function render()
    {
        $query = EconomicEvent::where('user_id', Auth::id())
            ->whereDate('date', $this->selectedDate)
            ->orderBy('time', 'asc');

        // Aplicar Filtros
        if (!empty($this->filterImpact)) {
            $query->whereIn('impact', $this->filterImpact);
        }
        if (!empty($this->filterCurrency)) {
            $query->whereIn('currency', $this->filterCurrency);
        }

        $events = $query->get();

        return view('livewire.economic-calendar-page', [
            'events' => $events
        ]);
    }
}
