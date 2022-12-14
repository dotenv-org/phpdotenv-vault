# PHP dotenv-vault


<img src="https://raw.githubusercontent.com/motdotla/dotenv/master/dotenv.svg" alt="dotenv-vault" align="right" width="200" />

## Installation

```shell
composer require dotenv-org/phpdotenv-vault
```

As early as possible in your application bootstrap process, load .env:

```php

use DotenvVault\DotenvVault;
require 'vendor/autoload.php';

$dotenv = DotenvVault::createImmutable(__DIR__, '.env.vault');
$dotenv->load(); # take environment variables from .env.vault

```

