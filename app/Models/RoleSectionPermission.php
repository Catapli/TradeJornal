<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleSectionPermission extends Model
{

    protected $guarded = ['id'];
    //
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
