<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Crypt;

class CredentialEncrypter
{
    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    public function decrypt(string $value): string
    {
        return Crypt::decryptString($value);
    }
}
