<?php

namespace App;

use App\Models\User;

trait AuthActions
{
    /**
     * Verifica si un usuario es Super Admin.
     *
     * @param User $user
     * @return bool
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->is_superadmin === true;
    }

    /**
     * Verifica si un usuario tiene permiso para realizar una acción en una sección.
     *
     * @param User $user
     * @param string $section Nombre de la sección (ej: 'alertas')
     * @param string $action Permiso ('r', 'w', 'd', 'x')
     * @return bool
     */
    protected function userCan(User $user, string $section, string $action): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->role?->hasPermission($section, $action) ?? false;
    }
}
