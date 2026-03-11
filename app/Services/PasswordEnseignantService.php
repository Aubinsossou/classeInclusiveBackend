<?php

namespace App\Services;

use App\Models\Enseignant;
use Illuminate\Support\Str;

class PasswordEnseignantService
{
    public function generateSecurePassword(int $length = 12): string
    {
        do {
            $uppercase = Str::upper(Str::random(2));
            $lowercase = Str::lower(Str::random(4));
            $numbers = rand(10, 99);

            $password = str_shuffle(
                $uppercase .
                $lowercase .
                $numbers 
            );

        } while (Enseignant::where('password', $password)->exists());

        return $password;
    }
}
