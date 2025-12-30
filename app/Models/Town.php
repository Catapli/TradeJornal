<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Town extends Model
{
    //
    use HasFactory;

    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function interest_lists()
    {
        return $this->hasMany(InterestList::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    // public function municipios()
    // {
    //     return $this->belongsToMany(Town::class, 'towns_relations', 'town_id', 'related_id');
    // }

    // public function relacionados()
    // {
    //     return $this->belongsToMany(Town::class, 'towns_relations', 'related_id', 'town_id');
    // }

    // Listas colaborativas en las que este municipio participa
    public function collaborativeLists()
    {
        return $this->belongsToMany(CollaborativeList::class, 'collaborative_list_town');
    }

    // Entradas que este municipio ha CREADO (es el autor)
    public function authoredInterestEntries()
    {
        return $this->hasMany(InterestList::class, 'town_id');
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }
}
