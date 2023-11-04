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

    protected function setUp(): void {
    }

    protected function tearDown(): void {
    }

    public function testThrowsExceptionIfUnableToLoadFile()
    {
        $dotenvVault = DotenvVault::createMutable(__DIR__);

        $this->expectExceptionMessage('Unable to read any of the environment file(s) at');

        $dotenvVault->load();
    }

    public function testTriesPathsToLoad()
    {
        $dotenvVault = DotenvVault::createImmutable([__DIR__, self::$folder]);
        self::assertCount(4, $dotenvVault->load());
    }

    public function testLoadFromEnvFile()
    {
        $dotenvVault = DotenvVault::createImmutable([__DIR__, self::$folder]);
        $dotenvVault->load();

        self::assertEquals($_ENV['FOO'], 'bar');
        self::assertEquals($_ENV['BAR'], 'baz');
    }

    public function testLoadFromEnvFileMutable()
    {
        $dotenvVault = DotenvVault::createMutable([__DIR__, self::$folder]);
        $dotenvVault->load();

        self::assertEquals($_ENV['FOO'], 'bar');
        self::assertEquals($_ENV['BAR'], 'baz');
    }

    public function testLoadFromEnvFileUnsafeMutable()
    {
        $dotenvVault = DotenvVault::createUnsafeMutable([__DIR__, self::$folder]);
        $dotenvVault->load();

        self::assertEquals(getenv('FOO'), 'bar');
        self::assertEquals(getenv('BAR'), 'baz');
    }

    public function testLoadFromEnvFileUnsafeImmutable()
    {
        $dotenvVault = DotenvVault::createUnsafeImmutable([__DIR__, self::$folder]);
        $dotenvVault->load();

        self::assertEquals(getenv('FOO'), 'bar');
        self::assertEquals(getenv('BAR'), 'baz');
    }

    public function testSafeLoadFromEnvFile()
    {
        $dotenvVault = DotenvVault::createImmutable([__DIR__, self::$folder]);
        $dotenvVault->safeLoad();

        self::assertEquals($_ENV['FOO'], 'bar');
        self::assertEquals($_ENV['BAR'], 'baz');
    }

    public function testLoadFromEnvFileAndPathsPassedAsString()
    {
        $dotenvVault = DotenvVault::createImmutable(self::$folder);
        $dotenvVault->load();

        self::assertEquals($_ENV['FOO'], 'bar');
        self::assertEquals($_ENV['BAR'], 'baz');
    }

    public function testLoadFromEnvVaultFileWhenDotenvKeyPresent()
    {
        $_ENV["DOTENV_KEY"] = 'dotenv://:key_ddcaa26504cd70a6fef9801901c3981538563a1767c297cb8416e8a38c62fe00@dotenv.org/vault/.env.vault?environment=development';

        $dotenvVault = DotenvVault::createImmutable([__DIR__, self::$folder]);
        $dotenvVault->load();

        self::assertEquals($_ENV['ALPHA'], 'zeta');
    }

    public function testLoadFromEnvVaultFileWhenDotenvKeyPresentAndPathsPassedAsString()
    {
        $_ENV["DOTENV_KEY"] = 'dotenv://:key_ddcaa26504cd70a6fef9801901c3981538563a1767c297cb8416e8a38c62fe00@dotenv.org/vault/.env.vault?environment=development';

        $dotenvVault = DotenvVault::createImmutable(self::$folder);
        $dotenvVault->load();

        self::assertEquals($_ENV['ALPHA'], 'zeta');
    }

    public function testLoadFromEnvVaultFileWhenDotenvKeyPresentMutable()
    {
        $_ENV["DOTENV_KEY"] = 'dotenv://:key_ddcaa26504cd70a6fef9801901c3981538563a1767c297cb8416e8a38c62fe00@dotenv.org/vault/.env.vault?environment=development';

        $dotenvVault = DotenvVault::createMutable([__DIR__, self::$folder]);
        $dotenvVault->load();

        self::assertEquals($_ENV['ALPHA'], 'zeta');
    }

    public function testLoadFromEnvVaultFileWhenDotenvKeyPresentUnsafeImmutable()
    {
        $_ENV["DOTENV_KEY"] = 'dotenv://:key_ddcaa26504cd70a6fef9801901c3981538563a1767c297cb8416e8a38c62fe00@dotenv.org/vault/.env.vault?environment=development';

        $dotenvVault = DotenvVault::createUnsafeImmutable([__DIR__, self::$folder]);
        $dotenvVault->load();

        self::assertEquals($_ENV['ALPHA'], 'zeta');
    }

    public function testLoadFromEnvVaultFileWhenDotenvKeyPresentUnsafeMutable()
    {
        $_ENV["DOTENV_KEY"] = 'dotenv://:key_ddcaa26504cd70a6fef9801901c3981538563a1767c297cb8416e8a38c62fe00@dotenv.org/vault/.env.vault?environment=development';

        $dotenvVault = DotenvVault::createUnsafeMutable([__DIR__, self::$folder]);
        $dotenvVault->load();

        self::assertEquals(getenv('ALPHA'), 'zeta');
    }
}
