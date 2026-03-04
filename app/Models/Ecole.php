<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;




class Ecole extends Authenticatable
{
      /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
protected $guarded = [];
      protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at'
    ];

     public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    public function enseignants(): HasMany
    {
        return $this->hasMany(Enseignant::class);
    }

    public function eleves(): HasManyThrough
    {
        return $this->hasManyThrough(
            Eleve::class,
            Classe::class
        );
    }

}
