<div align="center">

![Logo](docs/assets/img/logo.png)

# Composer update check plugin

[![Coverage](https://codecov.io/gh/eliashaeussler/composer-update-check/branch/main/graph/badge.svg?token=9AEQ0LRYU0)](https://codecov.io/gh/eliashaeussler/composer-update-check)
[![Maintainability](https://api.codeclimate.com/v1/badges/882ab3bb81b87d2b4a6d/maintainability)](https://codeclimate.com/github/eliashaeussler/composer-update-check/maintainability)
[![Tests](https://github.com/eliashaeussler/composer-update-check/actions/workflows/tests.yaml/badge.svg)](https://github.com/eliashaeussler/composer-update-check/actions/workflows/tests.yaml)
[![CGL](https://github.com/eliashaeussler/composer-update-check/actions/workflows/cgl.yaml/badge.svg)](https://github.com/eliashaeussler/composer-update-check/actions/workflows/cgl.yaml)
[![Release](https://github.com/eliashaeussler/composer-update-check/actions/workflows/release.yaml/badge.svg)](https://github.com/eliashaeussler/composer-update-check/actions/workflows/release.yaml)
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

## :ship: Changelog

View all notable release notes in the [Changelog](CHANGELOG.md).

## :gem: Credits

[Business vector created by studiogstock - www.freepik.com](https://www.freepik.com/vectors/business)

## :star: License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE.md).

[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Feliashaeussler%2Fcomposer-update-check.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2Feliashaeussler%2Fcomposer-update-check?ref=badge_large)
