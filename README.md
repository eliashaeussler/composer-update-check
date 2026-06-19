<div align="center">

![Logo](docs/assets/img/logo.png)

# Composer update check plugin

[![Coverage](https://img.shields.io/coverallsCoverage/github/eliashaeussler/composer-update-check?logo=coveralls)](https://coveralls.io/github/eliashaeussler/composer-update-check)
[![CI](https://img.shields.io/github/actions/workflow/status/eliashaeussler/composer-update-check/ci.yaml?label=CI&logo=github)](https://github.com/eliashaeussler/composer-update-check/actions/workflows/ci.yaml)
[![Latest Stable Version](https://poser.pugx.org/eliashaeussler/composer-update-check/v)](https://packagist.org/packages/eliashaeussler/composer-update-check)
[![Total Downloads](https://poser.pugx.org/eliashaeussler/composer-update-check/downloads)](https://packagist.org/packages/eliashaeussler/composer-update-check)
[![License](https://poser.pugx.org/eliashaeussler/composer-update-check/license)](LICENSE.md)

**:orange_book:&nbsp;[Documentation](https://composer-update-check.elias-haeussler.de/)** |
:package:&nbsp;[Packagist](https://packagist.org/packages/eliashaeussler/composer-update-check) |
:floppy_disk:&nbsp;[Repository](https://github.com/eliashaeussler/composer-update-check) |
:bug:&nbsp;[Issue tracker](https://github.com/eliashaeussler/composer-update-check/issues)

</div>

A Composer plugin that detects outdated dependencies in your `composer.lock`, based on the
version constraints in your `composer.json`. This distinguishes it from other plugins in the
wild, most of which do not respect version constraints. With an optional security scan and
an interface for other plugins, it provides an elegant way to highlight the successes of
your project. Especially in interaction with the
[reporter plugin](https://github.com/eliashaeussler/composer-update-reporter), it enables
automated quality assurance of your projects.

## :rocket: Features

* Detects outdated dependencies in your `composer.lock`, based on the version constraints
* Provides multiple exclusion patterns (ignore packages, skip dev-requirements)
* Optional security scan
* Smooth integration into Composer lifecycle
* Easy extensible via event listeners
* Optional [reporter plugin](https://github.com/eliashaeussler/composer-update-reporter)
  to create and send reports to various services

## :fire: Installation

```bash
composer require eliashaeussler/composer-update-check
```

## :gem: Credits

[Business vector created by studiogstock - www.freepik.com](https://www.freepik.com/vectors/business)

## :star: License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE.md).
