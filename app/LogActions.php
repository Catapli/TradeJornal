<?php

namespace App;

use App\Models\Log;

trait LogActions
{
    /**
     * Inserta un log en la base de datos
     *
     * @param int $user_id ID del usuario que realiza la acción
     * @param int|null $town_id ID del municipio sobre el cual se realiza la acción
     * @param string $form Nombre del formulario utilizado
     * @param string $action Nombre de la acción realizada
     * @param string|null $description Descripción adicional de la acción
     */
    protected function insertLog(
        int $user_id,
        int $town_id = null,
        string $form,
        string $action,
        string $description = null,
    ) {
        Log::create([
            'user_id' => $user_id,
            'town_id' => $town_id,
            'form' => $form,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
