<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    protected $guarded = [];
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }

    public function eleves(): HasMany
    {
        return $this->hasMany(Eleve::class);
    }

    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(
            Enseignant::class,
            'classe_enseignants'
        );
    }
}
