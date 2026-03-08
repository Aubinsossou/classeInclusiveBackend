<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Handicap extends Model
{
    protected $guarded = [];
    public function eleves(): HasMany
{
    return $this->hasMany(Eleve::class, 'handicap_id');
}
}
