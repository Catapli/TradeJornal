<?php

namespace App;

class MoneyHelper
{
    /**
     * Devuelve el símbolo de la moneda.
     */
    public static function getSymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD', 'CAD' => '$', // Dólares australianos/canadienses
            default => $currency, // Si no lo conoce, devuelve 'CHF', 'PLN', etc.
        };
    }
}
