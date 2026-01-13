<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramLevel extends Model
{
    //

    protected $guarded = ['id'];

    // 1. Relación con el Padre (Programa)
    // Esto soluciona el error: Call to undefined relationship [program]
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function objectives()
    {
        return $this->hasMany(ProgramObjective::class); // Tendrá fase 1, fase 2, y funded
    }
}
