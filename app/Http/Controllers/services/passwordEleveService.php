<?php

namespace App\Services;

use Illuminate\Support\Str;

class PasswordEleveService
{
    public function generateSecurePassword(int $length = 6): string
    {

        $numbers = rand(0, 99);
       ;

        return str_shuffle(
            $numbers
        );
    }
}
