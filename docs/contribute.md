# How to contribute

Contributions to the Composer update check plugin are very welcome. :slight_smile:

Please follow the guide on this page if you want to contribute. Make sure
that all required code quality checks are green. If you need help, feel free
to file an issue and I will try to assist you wherever needed.

## :octicons-terminal-24: Preparation

```bash
# Clone repository
git clone https://github.com/eliashaeussler/composer-update-check.git
cd composer-update-check

# Install Composer dependencies
composer install
```

## :octicons-file-code-24: Check code quality

Code quality can be checked by running the following commands:

```bash
# All linters
composer lint

# Specific linters
composer lint:composer
composer lint:editorconfig
composer lint:php

# Fix all CGL issues
composer fix

# Fix specific CGL issues
composer fix:composer
composer fix:editorconfig
composer fix:php

# All static code analyzers
composer sca

# Specific static code analyzers
composer sca:php
```

## :octicons-bug-24: Run tests

Unit tests can be executed using the provided Composer script `test`.
You can pass all available arguments to PHPUnit.

```bash
# Run tests
composer test

# Run tests and generate code coverage
composer test:coverage
```

## :technologist: Simulate application

A Composer script `simulate` exists which lets you run the Composer
command `update-check`. All parameters passed to the script will be
redirected to the Composer command.

```bash
# Run "composer update-check" command without parameters
composer simulate

# Pass parameters to "composer update-check" command
composer simulate -- -i "composer/*"
composer simulate -- --no-dev
```

Alternatively, this script can be called without Composer context:

```bash
./bin/simulate-application.sh
```

## :material-file-document-edit-outline: Build documentation

```bash
# Build documentation and watch for changes
composer docs

# Build documentation for production use
composer docs:build
```
