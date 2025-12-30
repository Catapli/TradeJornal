<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    //

    protected $guarded = ['id'];

    public function permissions()
    {
        return $this->hasMany(RoleSectionPermission::class);
    }

    public function hasUsers()
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $sectionName, string $action): bool
    {
        $permission = $this->permissions()
            ->whereHas('section', fn($q) => $q->where('name', $sectionName))
            ->first();

        if (!$permission) return false;

        return match ($action) {
            'r' => $permission->can_read,
            'w' => $permission->can_write,
            'd' => $permission->can_delete,
            'x' => $permission->can_download,
            default => false
        };
    }
}
