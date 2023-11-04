<?php

declare(strict_types=1);

namespace DotenvVault\Decrypter;

use Exception;

final class Decrypter implements DecrypterInterface
{
    /**
     * Decrypt encrypted content into a string
     *
     * @param string $content
     * @param string $keyStr
     *
     * @throws \Exception
     *
     * @return string
     */
    public function decrypt(string $encrypted, string $keyStr)
    {
        if ($encrypted === null || !is_string($encrypted) || strlen($encrypted) < 1) {
          $msg = 'MISSING_CIPHERTEXT: It must be a non-empty string';
          throw new Exception($msg);
        }

        // grab last 64 to permit keys like vlt_64 or custom_64
        $last64 = substr($keyStr, -64);

        // must be 64 characters long
        if (strlen($last64) !== 64) {
          $msg = 'INVALID_DOTENV_KEY: It must be 64 characters long (or more)';
          throw new Exception($msg);
        }

        // check key length is good INVALID_DOTENV_KEY: It must be 64 characters long (or more)
        $key = hex2bin($last64);

        // base64 decode
        $decoded = base64_decode($encrypted, true);

        // determine cipher and pull out nonce and tag
        $ciphertext = substr($decoded, 12, -16);
        $nonce = substr($decoded, 0, 12);
        $tag = substr($decoded, -16);
    
        try {
          $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);

          if ($plaintext === false) {
            $msg = 'DECRYPTION_FAILED: Please check your DOTENV_KEY';
            throw new Exception($msg);
          } else {
            return $plaintext;
          }
        } catch (ExceptionType $e) {
          $msg = 'DECRYPTION_FAILED: Please check your DOTENV_KEY';
          throw new Exception($msg);
        }
    }
}
