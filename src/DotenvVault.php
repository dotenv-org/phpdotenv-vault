<?php

declare(strict_types=1);

namespace DotenvVault;

use Dotenv\Dotenv;
use Exception;

class DotenvVault {
    public static function __callStatic($name, $arguments) {
        // Forward the call to the Dotenv\Dotenv class
        return call_user_func_array(['Dotenv\Dotenv', $name], $arguments);
    }

    // public function load()
    // {
    //     $this->dotenv_key = getenv("DOTENV_KEY");
    //     if ($this->dotenv_key !== false){

    //         $entries = $this->parser->parse($this->store->read());
    //         $this->loader->load($this->repository, $entries);

    //         $plaintext = $this->parse_vault();

    //         // parsing plaintext and loading to getenv 
    //         $vault_entries = $this->parser->parse($plaintext);
    //         return $this->loader->load($this->repository, $vault_entries);
    //     }
    //     else {
    //         $entries = $this->parser->parse($this->store->read());

    //         return $this->loader->load($this->repository, $entries);
    //     }
    // }

    // public function parse_vault()
    // {
    //     $dotenv_keys = explode(',', $this->dotenv_key);
    //     $keys = array();
    //     foreach($dotenv_keys as $key)
    //     {
    //         // parse DOTENV_KEY, format is a URI.
    //         $uri = parse_url(trim($key));

    //         // get encrypted key
    //         $pass = $uri['pass'];
    //         
    //         // Get environment from query params.
    //         parse_str($uri['query'], $params);
    //         $vault_environment = $params['environment'] or throw new Exception('INVALID_DOTENV_KEY: Missing environment part.');

    //         # Getting ciphertext from correct environment in .env.vault
    //         $vault_environment = strtoupper($vault_environment);
    //         $environment_key = "DOTENV_VAULT_{$vault_environment}";

    //         $ciphertext = getenv("{$environment_key}") or throw new Exception("NOT_FOUND_DOTENV_ENVIRONMENT: Cannot locate environment {$environment_key} in your .env.vault file. Run 'npx dotenv-vault build' to include it.");
    //         
    //         array_push($keys, array('encrypted_key' => $pass, 'ciphertext' => $ciphertext));
    //     }
    //     return $this->key_rotation($keys);
    // }

    // private function key_rotation($keys){
    //     $count = count($keys);
    //     foreach($keys as $index=>$value) {
    //         $decrypt = $this->decrypt($value['ciphertext'], $value['encrypted_key']);

    //         if ($decrypt == false && $index + 1 >= $count){
    //             throw new Exception('INVALID_DOTENV_KEY: Key must be valid.');
    //         }
    //         elseif($decrypt == false){
    //             continue;
    //         }
    //         else{
    //             return $decrypt;
    //         }
    //     }
    // }
}
