<?php

namespace App\Services;

use Illuminate\Support\Str;

class PasswordEleveService
{
   public function generateSecurePassword(int $length = 6): string
{
    do {
        $numbers = '';

        for ($i = 0; $i < $length; $i++) {
            $numbers .= random_int(0, 9);
        }

    } while (\App\Models\Eleve::where('code', $numbers)->exists());

    return $numbers;
}
}
