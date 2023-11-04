# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [Unreleased](https://github.com/dotenv-org/phpdotenv-vault/compare/v0.2.4...master)

## 0.2.4

### Changed

- Move logging to `error_log` rather than `echo`.

## 0.2.3

### Added

- echo/log out the dotenv-vault decryption message

## 0.2.2

### Added

- Added additional createX methods
- Added tests

## 0.2.1

### Changed

- Added support for passing string to paths argument.

## 0.2.0

### Added

- Moved decryption to its own class for better testing and ease of usage

### Fixed

- DOTENV_KEY was not respected if set in the infrastructure. Fixed.
- Decryptiong could fail related to some misconfigured logic. Fixed.

## 0.1.3

### Removed

- Remove `var_dump` when falling back to `.env` file

## 0.1.2

### Changed

- Use `getenv` instead of `$_ENV`

## 0.1.1

### Removed

- Remove laravel service provider that is not hooked up

## 0.1.0

### Added

- Basic functionality for decrypting `.env.vault` files supported
