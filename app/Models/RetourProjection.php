<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetourProjection extends Model
{
    protected $guarded = [];
    protected $table = 'retour_projection';

     public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
      public function cour(): BelongsTo
    {
        return $this->belongsTo(Cours::class);
    }
}
