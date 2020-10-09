[![Pipeline](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-check/badges/master/pipeline.svg)](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-check/-/pipelines)
[![Coverage](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-check/badges/master/coverage.svg)](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-check/-/pipelines)
[![Packagist](https://badgen.net/packagist/v/eliashaeussler/composer-update-check)](https://packagist.org/packages/eliashaeussler/composer-update-check)
[![License](https://badgen.net/packagist/license/eliashaeussler/composer-update-check)](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-check/-/blob/master/LICENSE)
[![Documentation](https://badgen.net/badge/read/the%20docs/cyan)](https://docs.elias-haeussler.de/composer-update-check/)

# Composer update check plugin

> A Composer Plugin to check outdated packages, based on their requirements.

## Features

* [x] Report outdated packages<br>
* [x] Multiple exclusion patterns (ignore packages, skip dev-requirements)<br>
* [x] Perform security scan<br>
* [x] Allow integration of additional plugins

## What are the differences to other plugins and commands?

Against other plugins and commands, this Plugin takes the **version constraints**
into account and reports outdated packages based on the individual **requirements**
in your `composer.json` file.

### Example

Given the following requirements of a `composer.json` file:

```json
{
  "require-dev": {
    "phpunit/phpunit": "~5.1.0"
  }
}
```

Using the native Composer command `composer outdated`, one can only check
if major or minor version updates are available. In this case the command
output would either show the major update (currently version `9.4.0`) or
the minor update to version `5.2.0`. 

The Composer command `composer update-check` provided by this plugin allows
checking for version updates based on the exact requirements. In this case
the output would show an available update to version `5.1.7`.

## License

This project is licensed under 
[GPL 3.0 (or later)](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-check/-/blob/master/LICENSE).
