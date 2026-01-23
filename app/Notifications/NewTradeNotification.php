<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewTradeNotification extends Notification
{
    use Queueable;

    public $trade;

    public function __construct($trade)
    {
        $this->trade = $trade;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $pnl = $this->trade->pnl;
        $symbol = $this->trade->tradeAsset->name ?? 'Activo';
        $amount = number_format(abs($pnl), 2) . "$";

        // BANCO DE FRASES (Psicología)
        if ($pnl >= 0) {
            // -- WIN (Refuerzo Positivo) --
            $type = "success";
            $titles = [
                "¡Take Profit Golpeado! 🎯",
                "¡Caja Registradora! 💸",
                "Excelente Ejecución 🚀",
                "Sincronización Completada ✅"
            ];
            $messages = [
                "Has sumado +{$amount} en {$symbol}. Gran lectura del mercado.",
                "El plan se ha cumplido. +{$amount} a la cuenta.",
                "Paciencia pagada. +{$amount}. Mantén la humildad.",
                "Ejecución limpia en {$symbol}. Sumas +{$amount}."
            ];

            // Selección aleatoria
            $title = $titles[array_rand($titles)];
            $message = $messages[array_rand($messages)];
        } else {
            // -- LOSS (Refuerzo de Disciplina/Calma) --
            $type = "error";
            $titles = [
                "Stop Loss Protegiendo 🛡️",
                "Costo del Negocio 📉",
                "Disciplina Mantenida 🧠",
                "Sincronización Completada ✅"
            ];
            $messages = [
                "Pérdida controlada de -{$amount} en {$symbol}. Respira y sigue.",
                "El SL te ha protegido de un daño mayor (-{$amount}). Bien gestionado.",
                "No persigas el precio. Acepta los -{$amount} y espera la siguiente oportunidad.",
                "El mercado tiene la razón. -{$amount}. Mantén la calma y revisa el análisis."
            ];

            $title = $titles[array_rand($titles)];
            $message = $messages[array_rand($messages)];
        }

        return [
            'trade_id' => $this->trade->id,
            'title' => $title,
            'message' => $message,
            'type' => $type, // 'success' o 'error'
            'pnl' => $pnl
        ];
    }
}
