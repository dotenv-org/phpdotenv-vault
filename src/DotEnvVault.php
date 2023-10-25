<?php

namespace DotenvVault;

use Dotenv\Dotenv;
use Dotenv\Loader\Loader;
use Dotenv\Loader\LoaderInterface;
use Dotenv\Parser\Parser;
use Dotenv\Parser\ParserInterface;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Store\StoreBuilder;
use Dotenv\Store\StoreInterface;
use Exception;

class DotEnvVault extends Dotenv
{
    /** @var StoreInterface */
    private $store;
    /** @var ParserInterface */
    private $parser;
    /** @var LoaderInterface */
    private $loader;
    /** @var RepositoryInterface */
    private $repository;
    /** @var string|null */
    private $dotenv_key;

    public function __construct(
        StoreInterface $store,
        ParserInterface $parser,
        LoaderInterface $loader,
        RepositoryInterface $repository
    ) {
        $this->store = $store;
        $this->parser = $parser;
        $this->loader = $loader;
        $this->repository = $repository;
    }

    /**
     * @throws DotEnvVaultError
     */
    public function load()
    {
        $vaultEntries = $this->parser->parse($this->store->read());
        $vaultArr = $this->loader->load($this->repository, $vaultEntries);

        $this->dotenv_key = $this->repository->get("DOTENV_KEY");
        if (empty($this->dotenv_key)) {
            return $vaultArr;
        }

        $decryptedStr = $this->parse_vault();

        // parsing plaintext and loading into repository
        $decryptedEntries = $this->parser->parse($decryptedStr);
        return $this->loader->load($this->repository, $decryptedEntries);
    }

    /**
     * Parses encrypted values within vault.
     * @throws DotEnvVaultError
     */
    public function parse_vault(): ?string
    {
        $dotenv_keys = explode(',', $this->dotenv_key);
        $keys = [];
        foreach($dotenv_keys as $key)
        {
            // parse DOTENV_KEY, format is a URI.
            $uri = parse_url(trim($key));

            // get encrypted key
            $pass = $uri['pass'];

            // Get environment from query params.
            parse_str($uri['query'], $params);
            if (!($vault_environment = $params['environment'] ?? false)) {
                throw new DotEnvVaultError('INVALID_DOTENV_KEY: Missing environment part.');
            }

            # Getting ciphertext from correct environment in .env.vault
            $vault_environment = strtoupper($vault_environment);
            $environment_key = "DOTENV_VAULT_{$vault_environment}";

            if (!($ciphertext = $this->repository->get($environment_key))) {
                throw new DotEnvVaultError("NOT_FOUND_DOTENV_ENVIRONMENT: Cannot locate environment {$environment_key} in your .env.vault file. Run 'npx dotenv-vault build' to include it.");
            }

            $keys[] = ['encrypted_key' => $pass, 'ciphertext' => $ciphertext];
        }
        return $this->key_rotation($keys);
    }

    /**
     * @throws DotEnvVaultError
     */
    private function key_rotation($keys): ?string
    {
        $count = count($keys);
        foreach($keys as $index=>$value) {
            $decrypt = $this->decrypt($value['ciphertext'], $value['encrypted_key']);

            if ($decrypt === false && $index + 1 >= $count){
                throw new DotEnvVaultError('INVALID_DOTENV_KEY: Key must be valid.');
            }
            elseif($decrypt === false){
                continue;
            }
            else{
                return $decrypt;
            }
        }

        return null;
    }

    private function decrypt($data, $secret)
    {
        $secret = hex2bin(substr($secret, 4, strlen($secret)));
        $data = base64_decode($data, true);
        $nonce = substr($data, 0, 12);
        $tag = substr($data, -16);
        $ciphertext = substr($data, 12, -16);

        try {
            return openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $secret,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag
            );
        } catch (Exception $e) {
            return false;
        }
    }

    public static function createImmutable($paths, $names = null, bool $shortCircuit = true, string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    public static function create(RepositoryInterface $repository, $paths, $names = null, bool $shortCircuit = true, string $fileEncoding = null)
    {
        $builder = $names === null ? StoreBuilder::createWithDefaultName() : StoreBuilder::createWithNoNames();

        foreach ((array) $paths as $path) {
            $builder = $builder->addPath($path);
        }

        foreach ((array) $names as $name) {
            $builder = $builder->addName($name);
        }

        if ($shortCircuit) {
            $builder = $builder->shortCircuit();
        }

        return new self($builder->fileEncoding($fileEncoding)->make(), new Parser(), new Loader(), $repository);
    }
}
