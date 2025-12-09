<?php

namespace App\Services;

class PasswordService
{
    public function generatePassword($length = 6): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $index = mt_rand(0, strlen($characters) - 1);
            $password .= $characters[$index];
        }

        return $password;
    }
}
