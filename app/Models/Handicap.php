<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Handicap extends Model
{
    protected $guarded = [];
      public function eleves()
    {
        return $this->belongsToMany(
            Eleve::class,
            'affecters'
        );
    }
}
