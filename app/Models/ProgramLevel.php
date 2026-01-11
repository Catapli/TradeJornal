<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramLevel extends Model
{
    //

    protected $guarded = ['id'];

    public function objectives()
    {
        return $this->hasMany(ProgramObjective::class); // Tendrá fase 1, fase 2, y funded
    }
}
