<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\EconomicEvent;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EconomicCalendar extends Component
{
    public $date;
    public $events = [];

    // Formulario para añadir rápido
    public $newTime = '14:30';
    public $newCurrency = 'USD';
    public $newEvent = '';
    public $newImpact = 'high';

    public function mount($date)
    {
        $this->date = $date;
        $this->loadEvents();
    }

    public function loadEvents()
    {
        // 1. Obtenemos las divisas que le importan al usuario
        $myCurrencies = $this->getMyTradedCurrencies();

        // 2. Query Principal
        $query = EconomicEvent::where('date', $this->date)
            ->where('impact', 'high') // Solo alto impacto como pediste
            ->orderBy('time', 'asc');

        // 3. Si el usuario ha operado algo, filtramos por sus divisas.
        // Si es un usuario nuevo (sin trades), mostramos las noticias de USD y EUR por defecto para no dejarlo vacío.
        if (!empty($myCurrencies)) {
            $query->whereIn('currency', $myCurrencies);
        } else {
            // Fallback opcional: Si no ha operado nunca, mostrar al menos las importantes globales
            $query->whereIn('currency', ['USD', 'EUR']);
        }

        $this->events = $query->get();
    }

    private function getMyTradedCurrencies()
    {
        // 1. Obtener símbolos únicos que ha operado el usuario
        $symbols = \App\Models\Trade::query()
            ->whereHas('account', fn($q) => $q->where('user_id', \Illuminate\Support\Facades\Auth::id()))
            ->join('trade_assets', 'trades.trade_asset_id', '=', 'trade_assets.id')
            ->distinct()
            ->pluck('trade_assets.symbol') // Ej: ["EURUSD", "XAUUSD", "US30", "GBP.JPY"]
            ->map(fn($s) => strtoupper($s));

        $currenciesToCheck = collect();

        // 2. Mapa de Activos "Raros" (Índices, Metales, Crypto) -> Divisa que les afecta
        $specialAssets = [
            'XAU' => 'USD',
            'GOLD' => 'USD', // Oro
            'XAG' => 'USD', // Plata
            'BTC' => 'USD',
            'ETH' => 'USD', // Crypto suele moverse con USD
            'NAS' => 'USD',
            'US30' => 'USD',
            'SPX' => 'USD',
            'US500' => 'USD', // Índices USA
            'GER' => 'EUR',
            'DE30' => 'EUR',
            'DE40' => 'EUR', // Índices Europa
            'UK100' => 'GBP', // Índice UK
            'JPN' => 'JPY',
            'JP225' => 'JPY', // Índice Japón
        ];

        // 3. Divisas Mayores (Las que usa el calendario económico)
        $majors = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'NZD', 'CNY'];

        foreach ($symbols as $symbol) {
            // A. Chequeo de Activos Especiales (Indices/Metales)
            foreach ($specialAssets as $key => $currency) {
                if (str_contains($symbol, $key)) {
                    $currenciesToCheck->push($currency);
                }
            }

            // B. Chequeo de Pares de Divisas (Forex)
            // Buscamos si el símbolo contiene "EUR", "USD", etc.
            foreach ($majors as $major) {
                if (str_contains($symbol, $major)) {
                    $currenciesToCheck->push($major);
                }
            }
        }

        // Retornamos array único (Ej: ['EUR', 'USD', 'JPY'])
        return $currenciesToCheck->unique()->values()->toArray();
    }


    // Método "Fake" para simular importación automática (Útil para demos)
    // En el futuro aquí conectarías una API real como Financial Modeling Prep
    public function importKeyEvents()
    {
        $presets = [
            ['14:30', 'USD', 'CPI (YoY)', 'high'],
            ['14:30', 'USD', 'Core PPI (MoM)', 'high'],
            ['20:00', 'USD', 'FOMC Meeting', 'high'],
        ];

        foreach ($presets as $p) {
            EconomicEvent::firstOrCreate([
                'user_id' => Auth::id(),
                'date' => $this->date,
                'event' => $p[2]
            ], [
                'time' => $p[0],
                'currency' => $p[1],
                'impact' => $p[3]
            ]);
        }
        $this->loadEvents();
    }

    public function render()
    {
        return view('livewire.economic-calendar');
    }
}
