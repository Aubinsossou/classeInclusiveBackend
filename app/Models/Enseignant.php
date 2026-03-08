<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;


class Enseignant extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'enseignant_api';

    protected $guarded = [

    ];
    protected $hidden = [
        'password',
        'remember_token',
        'updated_at'
    ];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }
    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class);
    }
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'enseignant_id');
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }
    public function scopeFull($query)
    {
        return $query->with([
            'ecole',
            'classe',
            'cours',
            'cours.medias'
        ]);
    }
}
