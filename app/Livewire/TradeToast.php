<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class TradeToast extends Component
{
    // Esta función se ejecutará automáticamente por el polling
    public function checkNotifications()
    {
        $user = Auth::user();

        // Buscamos solo las notificaciones NO leídas de nuestra clase
        // Ojo: Usamos el nombre de la clase como string para filtrar el 'type'
        $notifications = $user->unreadNotifications
            ->where('type', 'App\Notifications\NewTradeNotification');

        foreach ($notifications as $notification) {
            // Disparamos evento al navegador con los datos
            $this->dispatch('trigger-toast', $notification->data);

            // Marcamos como leída para que no vuelva a salir
            $notification->markAsRead();
        }
    }

    public function render()
    {
        return view('livewire.trade-toast');
    }
}
