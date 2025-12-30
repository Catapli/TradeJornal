<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    //
    public function permissions()
    {
        return $this->hasMany(RoleSectionPermission::class);
    }
}
