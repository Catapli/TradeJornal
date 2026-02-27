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
                __('labels.tp_hit'),
                __('labels.cash_register'),
                __('labels.excelent_execution'),
                __('labels.sync_complete')
            ];
            $messages = [
                __('labels.sum_mssg', ['amount' => $amount, 'symbol' => $symbol]),
                __('labels.mssg_tp_1', ['amount' => $amount]),
                __('labels.mssg_tp_2', ['amount' => $amount]),
                __('labels.mssg_tp_3', ['symbol' => $symbol, 'amount' => $amount]),
            ];

            // Selección aleatoria
            $title = $titles[array_rand($titles)];
            $message = $messages[array_rand($messages)];
        } else {
            // -- LOSS (Refuerzo de Disciplina/Calma) --
            $type = "error";
            $titles = [
                __('labels.title_sl_1'),
                __('labels.title_sl_2'),
                __('labels.title_sl_3'),
                __('labels.sync_complete')
            ];
            $messages = [
                __('labels.mssg_sl_1', ['amount' => $amount, 'symbol' => $symbol]),
                __('labels.mssg_sl_2', ['amount' => $amount]),
                __('labels.mssg_sl_3', ['amount' => $amount]),
                __('labels.mssg_sl_4', ['amount' => $amount]),
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
