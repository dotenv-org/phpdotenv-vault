<?php

declare(strict_types=1);

namespace DotenvVault\Decrypter;

interface DecrypterInterface
{
    /**
     * Decrypt encrypted content into a string
     *
     * @param string $content
     * @param string $keyStr
     *
     * @throws \Exception
     *
     *
     * @return string
     */
    public function decrypt(string $encrypted, string $keyStr);
}
