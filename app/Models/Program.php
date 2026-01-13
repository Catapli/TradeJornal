<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Program extends Model
{
    //

    protected $guarded = ['id'];

    // Esto soluciona el error: Call to undefined relationship [program]
    public function propFirm(): BelongsTo
    {
        return $this->belongsTo(PropFirm::class);
    }

    public function levels()
    {
        return $this->hasMany(ProgramLevel::class);
    }
}
