<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Eleve extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'eleve_api';

    protected $guarded = [

    ];
    protected $hidden = [
        'password',
        'remember_token',
        'updated_at'
    ];
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function handicaps(): BelongsToMany
    {
        return $this->belongsToMany(
            Handicap::class,
            'eleveHandicap'
        );
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
