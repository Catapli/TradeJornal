<?php

namespace App\Http\Controllers;

use App\Models\InterestList;
use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class NotificationController extends Controller
{
    public function sendPushNotification(Request $request)
    {
        $town = InterestList::select('town_id')->with(['town'])->where('plate', $request->plate)->first();
        $users = User::select("fcm_token")->where('fcm_token', '!=', null)->where('town_id', $town->town_id)->get();
        if (count($users) == 0) {
            return response()->json(['message' => 'No hay Usuarios']);
        }

        $title = $request->input('title', 'Nueva Alerta');
        $body = $request->input('body', 'Se ha registrado el vehículo: ' . $request->plate);

        foreach ($users as $value) {
            try {
                $message = CloudMessage::withTarget('token', $value->fcm_token)
                    ->withNotification(
                        \Kreait\Firebase\Messaging\Notification::create($title, $body)
                    )
                    // Opcional: añadir datos para manejar en la app
                    ->withData(['screen' => 'alertas']);

                Firebase::messaging()->send($message);

                return response()->json(['message' => 'Notificación enviada con éxito']);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Error al enviar notificación: ' . $e->getMessage()], 500);
            }
        }
    }
}
