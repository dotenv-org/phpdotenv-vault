<?php

declare(strict_types=1);

namespace DotenvVault\Tests;

// phpdotenv-vault libs
use DotenvVault\DotenvVault;

// phpdotenv libs
use Dotenv;

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

    public function testThrowsExceptionIfUnableToLoadFile()
    {
        $dotenv = DotenvVault::createMutable(__DIR__);

        $this->expectException(Dotenv\Exception\InvalidPathException::class);
        $this->expectExceptionMessage('Unable to read any of the environment file(s) at');

        $dotenv->load();
    }

    public function testTriesPathsToLoad()
    {
        $dotenv = DotenvVault::createMutable([__DIR__, self::$folder]);
        self::assertCount(4, $dotenv->load());
    }

    public function testLoadsFromEnvFile()
    {
        DotenvVault::createMutable([__DIR__, self::$folder]);

        self::assertEquals($_ENV['FOO'], 'bar');
        self::assertEquals($_ENV['BAR'], 'baz');
    }

    public function testLoadsFromEnvVaultFileWhenDotenvKeyPresent()
    {
        $_ENV["DOTENV_KEY"] = 'dotenv://:key_ddcaa26504cd70a6fef9801901c3981538563a1767c297cb8416e8a38c62fe00@dotenv.org/vault/.env.vault?environment=development';

        DotenvVault::createMutable([__DIR__, self::$folder]);

        self::assertEquals($_ENV['ALPA'], 'zeta');
    }
}
