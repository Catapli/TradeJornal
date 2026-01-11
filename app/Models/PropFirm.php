<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropFirm extends Model
{
    //
    protected $guarded = ['id'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
