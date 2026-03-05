<?php

namespace App\Services;

use Illuminate\Support\Str;

class PasswordEnseignantService
{
    public function generateSecurePassword(int $length = 12): string
    {
        $uppercase = Str::upper(Str::random(2));
        $lowercase = Str::lower(Str::random(4));
        $numbers = rand(10, 99);
        $symbols = ['@', '#', '$', '%', '&'];

        return str_shuffle(
            $uppercase .
            $lowercase .
            $numbers .
            $symbols[array_rand($symbols)]
        );
    }
}
