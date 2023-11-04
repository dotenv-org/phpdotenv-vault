<?php

declare(strict_types=1);

namespace DotenvVault\Tests\Decrypter;

use Exception;
use DotenvVault\Decrypter\Decrypter;
use DotenvVault\Decrypter\DecrypterInterface;
use PHPUnit\Framework\TestCase;

final class DecrypterTest extends TestCase
{
    protected $encrypted;

    protected function setUp(): void {
        $this->encrypted = 's7NYXa809k/bVSPwIAmJhPJmEGTtU0hG58hOZy7I0ix6y5HP8LsHBsZCYC/gw5DDFy5DgOcyd18R';
    }

    public function testDecrypterInstanceOf()
    {
        self::assertInstanceOf(DecrypterInterface::class, new Decrypter());
    }

    public function testFullDecrypt()
    {
        $keyStr = 'ddcaa26504cd70a6fef9801901c3981538563a1767c297cb8416e8a38c62fe00';

        $result = (new Decrypter())->decrypt($this->encrypted, $keyStr);

        self::assertEquals($result, "# development@v6\nALPHA=\"zeta\"");
    }

    public function testDecryptWhenKeyIsTooShort()
    {
        $keyStr = 'vlt_tooshort';

        $this->expectExceptionMessage('INVALID_DOTENV_KEY: It must be 64 characters long (or more)');

        $result = (new Decrypter())->decrypt($this->encrypted, $keyStr);

        self::assertEquals($result, "# development@v6\nALPHA=\"zeta\"");
    }

    public function testDecryptWhenKeyIsWrong()
    {
        $keyStr = 'AAcaa26504cd70a6fef9801901c3981538563a1767c297cb8416e8a38c62fe00';

        $this->expectExceptionMessage('DECRYPTION_FAILED: Please check your DOTENV_KEY');

        $result = (new Decrypter())->decrypt($this->encrypted, $keyStr);

        self::assertEquals($result, "# development@v6\nALPHA=\"zeta\"");
    }

    public function testDecryptEmptyEncryptedText()
    {
        $keyStr = 'ddcaa26504cd70a6fef9801901c3981538563a1767c297cb8416e8a38c62fe00';

        $this->expectExceptionMessage('MISSING_CIPHERTEXT: It must be a non-empty string');

        $result = (new Decrypter())->decrypt('', $keyStr);
    }
}
