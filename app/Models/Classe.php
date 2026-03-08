<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Classe extends Model
{
    protected $guarded = [];
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }

    public function eleves()
    {
        return $this->hasMany(Eleve::class);
    }
    public function enseignant(): HasOne
    {
        return $this->hasOne(Enseignant::class,'classe_id');
    }
}
