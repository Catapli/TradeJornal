<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mistake extends Model
{
    //

    protected $guarded = [];

    // 👇 ESTE ES EL MÉTODO QUE FALTA 👇
    public function scopeForUser($query, $userId)
    {
        // Devuelve los errores de este usuario O los globales (null)
        return $query->where('user_id', $userId)
            ->orWhereNull('user_id');
    }
}
