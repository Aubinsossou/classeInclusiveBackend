<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursMedias extends Model
{

    protected $guarded = [];
    public function cours(): BelongsTo
    {
        return $this->belongsTo(Cours::class);
    }
}
