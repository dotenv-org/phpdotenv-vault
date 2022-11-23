<?php

namespace DotenvVault;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
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
use Dotenv\Store\StringStore;

class DotEnvVault extends Dotenv
{
    private $store;
    private $parser;
    private $loader;
    private $repository;
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
        // parent::__construct($store, $parser, $loader, $repository);

    }

    public function load_vault()
    {
        echo "\n====LOAD FUNCTION====\n";
        $entries = $this->parser->parse($this->store->read());

        return $this->loader->load($this->repository, $entries);

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
