<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use function Pest\Laravel\get;

class Log extends Model
{

    protected
        $guarded = ['id'];
    //

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }


    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return Carbon::parse($value)->format('d-m-Y H:i:s');
            }
        );
    }
}
