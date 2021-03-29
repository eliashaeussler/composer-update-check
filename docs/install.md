# Installation

## Prerequisites

Installation is done using [Composer](https://getcomposer.org/).
Make sure to have Composer installed before using this Plugin.

The following code examples assume you have Composer globally installed.
In case you are using a local `composer.phar`, just replace the `composer`
command by the path to your `composer.phar` in the following examples.

### Requirements

* PHP 7.1+
* Composer v1 or v2

## Installation

### Using Composer

```bash
composer req --dev eliashaeussler/composer-update-check
```

### From source

```bash
composer config repositories.update-check vcs "https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-check.git"
composer req --dev eliashaeussler/composer-update-check
```
