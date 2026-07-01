<?php

namespace Tests\Unit;

use App\Services\Security\CredentialEncrypter;
use Tests\TestCase;

class CredentialEncrypterTest extends TestCase
{
    private CredentialEncrypter $encrypter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->encrypter = new CredentialEncrypter;
    }

    public function test_encrypt_decrypt_round_trip(): void
    {
        $plaintext = 'sup3r_s3cr3t_p@ssw0rd';

        $encrypted = $this->encrypter->encrypt($plaintext);
        $decrypted = $this->encrypter->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_encrypted_value_differs_from_plaintext(): void
    {
        $plaintext = 'my-secret-key-12345';

        $encrypted = $this->encrypter->encrypt($plaintext);

        $this->assertNotSame($plaintext, $encrypted);
        $this->assertStringNotContainsString($plaintext, $encrypted);
    }

    public function test_same_value_produces_different_encrypted_output(): void
    {
        $plaintext = 'sensitive-data';

        $encrypted1 = $this->encrypter->encrypt($plaintext);
        $encrypted2 = $this->encrypter->encrypt($plaintext);

        $this->assertNotSame($encrypted1, $encrypted2);
    }

    public function test_works_with_empty_string(): void
    {
        $encrypted = $this->encrypter->encrypt('');
        $decrypted = $this->encrypter->decrypt($encrypted);

        $this->assertSame('', $decrypted);
    }

    public function test_works_with_special_characters(): void
    {
        $plaintext = 'p@$$w0rd!\"\'\\\n\t';

        $encrypted = $this->encrypter->encrypt($plaintext);
        $decrypted = $this->encrypter->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    public function test_works_with_long_string(): void
    {
        $plaintext = str_repeat('A', 10000);

        $encrypted = $this->encrypter->encrypt($plaintext);
        $decrypted = $this->encrypter->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }
}
