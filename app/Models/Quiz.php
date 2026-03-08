<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
      protected $table = 'quizzes';
    protected $guarded = [];

    public function cours(): BelongsTo
    {
        return $this->belongsTo(Cours::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }
    public function reponses() {
    return $this->hasMany(Reponse::class, 'question_id');
}
}
