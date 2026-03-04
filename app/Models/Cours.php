<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cours extends Model
{
    protected $guarded = [];
     public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function quiz(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }
}
