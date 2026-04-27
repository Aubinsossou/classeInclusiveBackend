<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Strategies extends Model
{
    protected $guarded = [];

     public function cour(): BelongsTo
    {
        return $this->belongsTo(Cours::class);
    }
}
