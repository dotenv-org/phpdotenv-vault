<?php

declare(strict_types=1);

namespace DotenvVault;

use Dotenv\Dotenv;
use Dotenv\Loader\Loader;
use Dotenv\Loader\LoaderInterface;
use Dotenv\Parser\Parser;
use Dotenv\Parser\ParserInterface;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Store\StoreBuilder;
use Dotenv\Store\StoreInterface;
use Dotenv\Util\Str;
use PhpOption\Option;
use Exception;

use DotenvVault\Decrypter\Decrypter;
use DotenvVault\Decrypter\DecrypterInterface;

class DotenvVault extends Dotenv {
    /**
     * Keep track of the original paths
     * $paths string|string[]
     */
    private $paths;

    private $store;
    private $parser;
    private $loader;
    private $repository;

    public function __construct(
        StoreInterface $store,
        ParserInterface $parser,
        LoaderInterface $loader,
        RepositoryInterface $repository,
        $paths = null
    )
    {
        $this->store = $store;
        $this->parser = $parser;
        $this->loader = $loader;
        $this->repository = $repository;
        $this->paths = $paths;

        parent::__construct($store, $parser, $loader, $repository);
    }

    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string|string[]                        $paths
     * @param string|string[]|null                   $names
     * @param bool                                   $shortCircuit
     * @param string|null                            $fileEncoding
     *
     * @return \Dotenv\Dotenv
     */
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

        return new self($builder->fileEncoding($fileEncoding)->make(), new Parser(), new Loader(), $repository, $paths);
    }

    /**
     * Create a new mutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return \DotenvVault\DotenvVault
     */
    public static function createMutable($paths, $names = null, bool $shortCircuit = true, string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new mutable dotenv instance with default repository with the putenv adapter.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return \DotenvVault\DotenvVault
     */
    public static function createUnsafeMutable($paths, $names = null, bool $shortCircuit = true, string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new immutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return \DotenvVault\DotenvVault
     */
    public static function createImmutable($paths, $names = null, bool $shortCircuit = true, string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    /**
     * Create a new immutable dotenv instance with default repository with the putenv adapter.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     * @param string|null          $fileEncoding
     *
     * @return \DotenvVault\DotenvVault
     */
    public static function createUnsafeImmutable($paths, $names = null, bool $shortCircuit = true, string $fileEncoding = null)
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->immutable()
            ->make();

        return self::create($repository, $paths, $names, $shortCircuit, $fileEncoding);
    }

    public function load()
    {
        $vaultPath = $this->_vaultPath();

        // fallback to original dotenv if DOTENV_KEY is not set
        if (strlen($this->_dotenvKey()) === 0) {
            return $this->_loadDotenv();
        }

        // // dotenvKey exists but .env.vault file does not exist
        if (!$vaultPath || !file_exists($vaultPath)) {
            trigger_error("You set DOTENV_KEY but you are missing a .env.vault file at '{$vaultPath}'. Did you forget to build it?", E_USER_WARNING);

            return $this->_loadDotenv();
        }

        $this->_loadVault();
    }

    //
    // public functions treated like private functions.
    // exposed publicly for convenience of your consumption.
    //

    public function _log($message)
    {
        error_log("[dotenv-vault][INFO] {$message}");
    }

    public function _loadDotenv()
    {
        // $entries = $this->parser->parse($this->store->read());
        // return $this->loader->load($this->repository, $entries);

        return parent::load();
    }

    public function _loadVault() {
        $this->_log('Loading env from encrypted .env.vault');

        $decrypted = $this->_decryptVault();

        $vaultEntries = $this->parser->parse($decrypted);

        return $this->loader->load($this->repository, $vaultEntries);
    }

    public function _decryptVault()
    {
        $vaultPath = $this->_vaultPath();

        // .env.vault as string
        $content = file_get_contents($vaultPath);

        // Parse .env.vault file with Dotenv parser
        $entries = (new Parser())->parse($content);

        // built DOTENV_${ENVIRONMENT} lookups
        $lookups = [];
        foreach ($entries as $entry) {
            $key = $entry->getName();
            $value = $entry->getValue()->get()->getChars(); // really ugly api here from the original phpdotenv lib.

            $lookups[$key] = $value;
        }

        // handle scenario for comma separated keys - for use with key rotation
        // example: DOTENV_KEY="dotenv://:key_1234@dotenv.org/vault/.env.vault?environment=prod,dotenv://:key_7890@dotenv.org/vault/.env.vault?environment=prod"
        $keys = explode(',', $this->_dotenvKey());

        $decrypted = null;

        for ($i = 0; $i < count($keys); $i++) {
            try {
                // Get full key
                $key = trim($keys[$i]);

                // Get instructions for decrypt
                $attrs = $this->_instructions($lookups, $key);

                // Decrypt
                $decrypted = (new Decrypter())->decrypt($attrs['ciphertext'], $attrs['key']);
                
                // If successful, break the loop
                break;
            } catch (Exception $error) {
                // if last key
                if ($i + 1 >= count($keys)) {
                    // rethrow the exception
                    throw $error;
                }
                // Otherwise, the loop will continue to the next key
            }

        }

        return $decrypted;
    }

    public function _instructions($lookups, $dotenvKey) {
        // Parse DOTENV_KEY. Format is a URI
        $uri = parse_url($dotenvKey);
        if ($uri === false) {
            throw new Exception('INVALID_DOTENV_KEY: Wrong format. Must be in valid URI format like dotenv://key_1234@dotenv.org/vault/.env.vault?environment=development');
        }

        // Get decrypt key
        $key = $uri['pass'] ?? null;
        if (!$key) {
            throw new Exception('INVALID_DOTENV_KEY: Missing key part');
        }

        // Get environment
        parse_str($uri['query'], $queryParams);
        $environment = $queryParams['environment'] ?? null;
        if (!$environment) {
            throw new Exception('INVALID_DOTENV_KEY: Missing environment part');
        }

        // Get ciphertext payload
        $environmentKey = 'DOTENV_VAULT_' . strtoupper($environment);
        $ciphertext = $lookups[$environmentKey] ?? null;
        if (!$ciphertext) {
            throw new Exception("NOT_FOUND_DOTENV_ENVIRONMENT: Cannot locate environment {$environmentKey} in your .env.vault file.");
        }

        return ['ciphertext' => $ciphertext, 'key' => $key];
    }

    public function _dotenvKey()
    {
        $dotenv_key = $_ENV['DOTENV_KEY'] ?? $_SERVER['DOTENV_KEY'] ?? getenv('DOTENV_KEY');

        // infra already contains a DOTENV_KEY environment variable
        if ($dotenv_key && strlen($dotenv_key) > 0) {
            return $dotenv_key;
        }

        // fallback to empty string
        return '';
    }

    public function _vaultPath()
    {
        $dotenvVaultPath = null;

        foreach ((array) $this->paths as $dotenvPath) {
            // Check if the path ends with '.vault'. If not, append '.vault' to the path.
            $dotenvPath .= '/.env.vault';

            // check if .env.vault exists
            if (file_exists($dotenvPath)) {
                $dotenvVaultPath = $dotenvPath;
                break;
            }
        }

        return $dotenvVaultPath;
    }
}
