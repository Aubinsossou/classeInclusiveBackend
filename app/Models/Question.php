<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $guarded = [];
     public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function reponses(): HasMany
    {
        return $this->hasMany(Reponse::class);
    }
}
