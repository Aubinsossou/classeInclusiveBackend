<?php

namespace App\Services;

use Illuminate\Support\Str;

class PasswordEleveService
{
    public function generateSecurePassword(int $length = 6): string
    {
        $numbers = '';

        for ($i = 0; $i < $length; $i++) {
            $numbers .= rand(0, 9);
        }

        return $numbers;
    }
}
