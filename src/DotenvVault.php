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

class DotEnvVaultError extends Exception { }

class DotEnvVault extends Dotenv
{
    private $store;
    private $parser;
    private $loader;
    private $repository;
    private $dotenv_key;
    public function __construct(
        StoreInterface $store,
        ParserInterface $parser,
        LoaderInterface $loader,
        RepositoryInterface $repository
    )
    {
        $this->store = $store;
        $this->parser = $parser;
        $this->loader = $loader;
        $this->repository = $repository;
    }

    public function load()
    {
        $this->dotenv_key = getenv("DOTENV_KEY");
        if ($this->dotenv_key !== false){

            $entries = $this->parser->parse($this->store->read());
            $this->loader->load($this->repository, $entries);

            $plaintext = $this->parse_vault();

            // parsing plaintext and loading to $_ENV
            $test_entries = $this->parser->parse($plaintext);
            $this->loader->load($this->repository, $test_entries);
        }
        else {
            $entries = $this->parser->parse($this->store->read());

            var_dump($entries[0]->getName());
            var_dump($entries[0]->getValue());

            return $this->loader->load($this->repository, $entries);
        }
    }

    public function parse_vault()
    {
        $dotenv_keys = explode(',', $this->dotenv_key);
        $keys = array();
        foreach($dotenv_keys as $key)
        {
            // parse DOTENV_KEY, format is a URI.
            $uri = parse_url(trim($key));

            // get encrypted key
            $pass = $uri['pass'];
            
            // Get environment from query params.
            parse_str($uri['query'], $params);
            $vault_environment = $params['environment'] or throw new DotEnvVaultError('INVALID_DOTENV_KEY: Missing environment part.');

            # Getting ciphertext from correct environment in .env.vault
            $vault_environment = strtoupper($vault_environment);
            $environment_key = "DOTENV_VAULT_{$vault_environment}";

            $ciphertext = $_ENV["{$environment_key}"] or throw new DotEnvVaultError("NOT_FOUND_DOTENV_ENVIRONMENT: Cannot locate environment {$environment_key} in your .env.vault file. Run 'npx dotenv-vault build' to include it.");
            
            array_push($keys, array('encrypted_key' => $pass, 'ciphertext' => $ciphertext));
        }
        return $this->key_rotation($keys);
    }

    private function key_rotation($keys){
        $count = count($keys);
        foreach($keys as $index=>$value) {
            $decrypt = $this->decrypt($value['ciphertext'], $value['encrypted_key']);

            if ($decrypt == false && $index + 1 >= $count){
                throw new DotEnvVaultError('INVALID_DOTENV_KEY: Key must be valid.');
            }
            elseif($decrypt == false){
                continue;
            }
            else{
                return $decrypt;
            }
        }
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
