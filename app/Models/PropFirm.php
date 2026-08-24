<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropFirm extends Model
{
    use HasFactory;

    /** Clave de caché del árbol de prop firms usada en los selects de cuentas. */
    public const CACHE_KEY = 'prop_firms_data';

    protected $guarded = ['id'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
