<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoCours extends Model
{
    protected $guarded = [];
     public function cours()
    {
        return $this->belongsTo(Cours::class);
    }
}
