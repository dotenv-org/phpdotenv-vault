<?php

declare(strict_types=1);

namespace DotenvVault\Tests;

use DotenvVault\DotenvVault;
use PHPUnit\Framework\TestCase;

final class DotenvVaultTest extends TestCase
{
    /**
     * @var string
     */
    private static $folder;

    /**
     * @beforeClass
     *
     * @return void
     */
    public static function setFolder()
    {
        self::$folder = \dirname(__DIR__).'/fixtures/env';
    }

    public function testDotenvThrowsExceptionIfUnableToLoadFile()
    {
        $dotenv = DotenvVault::createMutable(__DIR__);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Unable to read any of the environment file(s) at');

        $dotenv->load();
    }
}
?>
