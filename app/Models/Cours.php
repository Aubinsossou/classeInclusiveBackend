<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cours extends Model
{
    protected $guarded = [];
    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }



    // Médias Cloudinary
    public function medias(): HasMany
    {
        return $this->hasMany(CoursMedias::class)
            ->orderBy('ordre');
    }

    // Quiz du cours
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    // Accesseur — média principal (video > image)
    public function getThumbnailAttribute(): ?string
    {
        return $this->medias->firstWhere('type', 'video')?->url
            ?? $this->medias->firstWhere('type', 'image')?->url;
    }

}
