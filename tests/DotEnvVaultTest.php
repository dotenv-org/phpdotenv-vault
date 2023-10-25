<?php

namespace DotenvVault\Tests;

use Dotenv\Loader\LoaderInterface;
use Dotenv\Parser\Entry;
use Dotenv\Parser\ParserInterface;
use Dotenv\Parser\Value;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Store\StoreInterface;
use DotenvVault\DotEnvVault;
use DotenvVault\DotEnvVaultError;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DotEnvVaultTest extends MockeryTestCase
{
    private $vaultKeyDev = 'DOTENV_VAULT_DEVELOPMENT';
    private $keyDev = 'dotenv://:key_12428c2d50a7bf9f0eef6cf4c26dd6ac6b88bf5bc081dd859f6e5a0ab3a56aae@dotenv.local/vault/.env.vault?environment=development';
    private $encryptedDev = 'p8xMNWI8rdR8pMHdsUdD70fIKnpIws/bx2ur+LrP/QEljrCN9EowXaH5+4t2jg6EuSljOb+TLQ==';
    private $decryptedDevStr = <<<ENV
APP_ENV=development
FOO=bar
ENV;
    private $decryptedDevArr = ['APP_ENV' => 'development', 'FOO' => 'bar'];

    private $vaultKeyProd = 'DOTENV_VAULT_PRODUCTION';
    private $keyProd = 'dotenv://:key_f99471b404c2cb6e321f7fd97abba8be17a343b429cef02e615db8576f11d310@dotenv.local/vault/.env.vault?environment=production';
    private $encryptedProd = '0EWcDIA9YidGiRtMMag/ggftCEfA+hKG5gGYC5Z5bXue/20uM850AgRuMhhbhfGkvZatz+yX';
    private $decryptedProdStr = <<<ENV
FOO=baz
APP_ENV=production
ENV;
    private $decryptedProdArr = ['FOO' => 'baz', 'APP_ENV' => 'production'];

    private $vaultFile;
    /** @var Entry[] */
    private $vaultFileEntries;
    /** @var string[] */
    private $vaultFileArr;

    /** @return Entry[] */
    private function makeDecryptedDevEntries(): array
    {
        return [
            new Entry('APP_ENV', Value::blank()->append('development', true)),
            new Entry('FOO', Value::blank()->append('bar', true)),
        ];
    }

    /** @return Entry[] */
    private function makeDecryptedProdEntries(): array
    {
        return [
            new Entry('FOO', Value::blank()->append('baz', true)),
            new Entry('APP_ENV', Value::blank()->append('production', true)),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->vaultFile = <<<ENV
# .env.vault (generated with npx dotenv-vault local build)
{$this->vaultKeyDev}="{$this->encryptedDev}"
{$this->vaultKeyProd}="{$this->encryptedProd}"

ENV;
        $this->vaultFileEntries = [
            new Entry($this->vaultKeyDev, Value::blank()->append($this->encryptedDev, true)),
            new Entry($this->vaultKeyDev, Value::blank()->append($this->encryptedProd, true)),
        ];
        $this->vaultFileArr = [$this->vaultKeyDev => $this->encryptedDev, $this->vaultKeyProd => $this->encryptedProd];
    }

    /** @dataProvider getLoadWithKeyData */
    public function testLoadWithKey(
        string $key,
        string $vaultName,
        string $encryptedVault,
        string $decryptedVault,
        array $decryptedEntries,
        array $decryptedArr
    ): void {
        $repository = Mockery::mock(RepositoryInterface::class);
        $repository->allows()->get('DOTENV_KEY')->andReturn($key);
        $repository->allows()->get($vaultName)->andReturn($encryptedVault);

        $store = Mockery::mock(StoreInterface::class);
        $store->allows()->read()->andReturn($this->vaultFile)->atLeast()->once();

        $parser = Mockery::mock(ParserInterface::class);
        $parser->allows()->parse($this->vaultFile)->andReturn($this->vaultFileEntries)->atLeast()->once();
        $parser->allows()->parse($decryptedVault)->andReturn($decryptedEntries)->atLeast()->once();

        $loader = Mockery::mock(LoaderInterface::class);
        $loader->allows()->load($repository, $this->vaultFileEntries)->andReturn($this->vaultFileArr)->atLeast()->once();
        $loader->allows()->load($repository, $decryptedEntries)->andReturn($decryptedArr)->atLeast()->once();

        $inst = new DotEnvVault($store, $parser, $loader, $repository);
        $actual = $inst->load();

        $this->assertSame($decryptedArr, $actual);
    }

    public function getLoadWithKeyData(): array
    {
        $decryptedDevEntries = $this->makeDecryptedDevEntries();
        $decryptedProdEntries = $this->makeDecryptedProdEntries();

        return [
            'dev' => [
                'key' => $this->keyDev,
                'vaultName' => $this->vaultKeyDev,
                'encryptedVault' => $this->encryptedDev,
                'decryptedVault' => $this->decryptedDevStr,
                'decryptedEntries' => $decryptedDevEntries,
                'decryptedArr' => $this->decryptedDevArr,
            ],
            'prod' => [
                'key' => $this->keyProd,
                'vaultName' => 'DOTENV_VAULT_PRODUCTION',
                'encryptedVault' => $this->encryptedProd,
                'decryptedVault' => $this->decryptedProdStr,
                'decryptedEntries' => $decryptedProdEntries,
                'decryptedArr' => $this->decryptedProdArr,
            ],
            'multi keys, valid first' => [
                'key' => "{$this->keyDev},dotenv://:key_0000000000000000000000000000000000000000000000000000000000000000@dotenv.local/vault/.env.vault?environment=development",
                'vaultName' => $this->vaultKeyDev,
                'encryptedVault' => $this->encryptedDev,
                'decryptedVault' => $this->decryptedDevStr,
                'decryptedEntries' => $decryptedDevEntries,
                'decryptedArr' => $this->decryptedDevArr,
            ],
            'multi keys, valid last' => [
                'key' => "dotenv://:key_0000000000000000000000000000000000000000000000000000000000000000@dotenv.local/vault/.env.vault?environment=production,{$this->keyProd}",
                'vaultName' => $this->vaultKeyProd,
                'encryptedVault' => $this->encryptedProd,
                'decryptedVault' => $this->decryptedProdStr,
                'decryptedEntries' => $decryptedProdEntries,
                'decryptedArr' => $this->decryptedProdArr,
            ],
        ];
    }

    public function testLoadWithoutKey(): void
    {
        $repository = Mockery::mock(RepositoryInterface::class);
        $repository->allows()->get('DOTENV_KEY')->andReturn('');

        $store = Mockery::mock(StoreInterface::class);
        $store->allows()->read()->andReturn($this->vaultFile)->atLeast()->once();

        $parser = Mockery::mock(ParserInterface::class);
        $parser->allows()->parse($this->vaultFile)->andReturn($this->vaultFileEntries)->atLeast()->once();

        $loader = Mockery::mock(LoaderInterface::class);
        $loader->allows()->load($repository, $this->vaultFileEntries)->andReturn($this->vaultFileArr)->atLeast()->once();

        $inst = new DotEnvVault($store, $parser, $loader, $repository);
        $actual = $inst->load();

        $this->assertSame($this->vaultFileArr, $actual);
    }

    public function testLoadWithInvalidKey(): void
    {
        $repository = Mockery::mock(RepositoryInterface::class);
        $repository->allows()->get('DOTENV_KEY')->andReturn('dotenv://:key_0000000000000000000000000000000000000000000000000000000000000000@dotenv.local/vault/.env.vault?environment=production');
        $repository->allows()->get($this->vaultKeyProd)->andReturn($this->encryptedProd);

        $store = Mockery::mock(StoreInterface::class);
        $store->allows()->read()->andReturn($this->vaultFile)->atLeast()->once();

        $parser = Mockery::mock(ParserInterface::class);
        $parser->allows()->parse($this->vaultFile)->andReturn($this->vaultFileEntries)->atLeast()->once();

        $loader = Mockery::mock(LoaderInterface::class);
        $loader->allows()->load($repository, $this->vaultFileEntries)->andReturn($this->vaultFileArr)->atLeast()->once();

        $inst = new DotEnvVault($store, $parser, $loader, $repository);

        $this->expectExceptionObject(new DotEnvVaultError('INVALID_DOTENV_KEY: Key must be valid.'));
        $inst->load();
    }

    public function testCreate(): void
    {
        $actual = DotEnvVault::create(Mockery::mock(RepositoryInterface::class),'path', '.env.vault');

        $this->assertInstanceOf(DotEnvVault::class, $actual);
    }

    public function testCreateImmutable(): void
    {
        $actual = DotEnvVault::createImmutable('path', '.env.vault');

        $this->assertInstanceOf(DotEnvVault::class, $actual);
    }
}
