<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Matiere extends Model
{
    protected $guarded = [];
    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class);
    }
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }
    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'classe_matieres')
            ->withPivot('ecole_id')
            ->withTimestamps();
    }
}
