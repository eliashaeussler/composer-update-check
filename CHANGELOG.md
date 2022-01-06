# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.3] - 2022-01-06

### Added

- Support for Symfony 6 components

## [1.1.2] - 2021-12-27

### Fixed

- Various CI fixes

## [1.1.1] - 2021-12-27

### Fixed

- Various CI fixes

## [1.1.0] - 2021-12-27

### Fixed

- Various requirements for dependencies installed with `--prefer-lowest`
- Requirements for PHP 7.1 compatibility
- Requirements for PHP 8.0 compatibility

### Added

- Support for Composer 2.2
- Code quality assurance with SonarCloud

### Changed

- Migrate project from GitLab to GitHub
- Upgrade PHP-CS-Fixer to 3.x
- Upgrade PHPStan to 1.x

### Documentation

- Improved README.md
- Add logo

## [1.0.2] - 2021-10-14

### Fixed

- Hostname of docs server

## [1.0.1] - 2021-10-14

### Fixed

- Autoload files from dependencies

## [1.0.0] - 2021-04-19

### Added

- Transfer objects for output style and verbosity
- Support for PHP 8.0
- Normalization of `composer.json`

### Changed

- Decouple `PostUpdateCheckEvent` from command event
- Various improvements in documentation
- Use temporary directories for test applications in Unit tests
- Parallel execution of Unit tests in CI

### Deprecated

- Usage with Docker images (will be removed in 2.0.0)

### Fixed

- Remove unneeded package dependency `composer/semver`
- Render all JavaScripts in documentation

## [0.8.2] - 2021-03-29

### Added

- PHPStan for static code analysis

### Changed

- Use Symfony rules in PHP-CS-Fixer

### Fixed

- Include all installed packages and sub-dependencies in native update command

## [0.8.1] - 2021-03-13

### Fixed

- Install required dependencies for documentation rendering

## [0.8.0] - 2021-03-13

### Changed

- Replace Guzzle by more lightweight libraries

### Fixed

- Revert loading of Composer dependencies
- Switch to PHP as base Docker image and explicitly define PHP version

## [0.7.3] - 2021-01-26

### Changed

- Return outdated packages in sorted order
- Use `master` branch of project dependents to test their successful integration

### Fixed

- Include notice about conflicting requirements of `composer/semver` package to user-oriented console output
- Avoid conflicts with Composer library in test applications

## [0.7.2] - 2021-01-18

### Added

- Tests for project dependents in CI
- PHP-CS-Fixer for linting PHP

### Changed

- Make code PSR-2 compliant
- Collect code coverage for PHP 7.4 & Composer 2 job only

## [0.7.1] - 2021-01-16

### Added

- CI variable `$RENDER_DOCS` to manually create project documentation
- Build and deploy Docker test image on each CI build
- Run application tests in Docker containers within CI

### Changed

- Skip security scan if no scanned packages are outdated

### Fixed

- Load missing Composer dependencies in Plugin entrypoint

## [0.7.0] - 2021-01-15

### Changed

- Use Packagist API instead of Composer package to check for insecure packages

### Fixed

- Ensure simulated application is cleaned up properly

## [0.6.1] - 2020-11-20

### Fixed

- Handling of SSH keys in Docker containers

## [0.6.0] - 2020-11-19

### Added

- Standalone Docker image `eliashaeussler/composer-update-check` for Composer 1 and 2

## [0.5.0] - 2020-11-16

### Added

- Support for Composer 2

## [0.4.3] - 2020-10-26

### Added

- Add provider link (Packagist URL) property to `OutdatedPackage`

## [0.4.0] - 2020-10-09

### Added

- Project documentation

### Changed

- Move update check to standalone API

## [0.3.0] - 2020-09-22

### Added

- Optional security scan using the `--security-scan` option

### Fixed

- Support for Composer versions < 1.10.8

## [0.2.0] - 2020-09-21

### Added

- New `--json` option for console command
- Show number of skipped packages to command success message
- Official support for PHP 7.1 - 7.4
- Application simulation testing

### Changed

- Use native Composer installer for installs und updates

### Fixed

- Include dev-requirements in Composer installer
- Show skipped dev-requirements in user-oriented console output

## [0.1.3] - 2020-09-17

### Fixed

- Minor fixes in user-oriented console output

## [0.1.2] - 2020-09-17

### Fixed

- Hide sub-command output from user-oriented console output

## [0.1.1] - 2020-09-17

### Added

- Add emojis to user-oriented console output

## [0.1.0] - 2020-09-16

Initial release

[Unreleased]: https://github.com/eliashaeussler/composer-update-check/compare/1.1.3...develop
[1.1.3]: https://github.com/eliashaeussler/composer-update-check/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/eliashaeussler/composer-update-check/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/eliashaeussler/composer-update-check/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/eliashaeussler/composer-update-check/compare/1.0.2...1.1.0
[1.0.2]: https://github.com/eliashaeussler/composer-update-check/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/eliashaeussler/composer-update-check/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/eliashaeussler/composer-update-check/compare/0.8.2...1.0.0
[0.8.2]: https://github.com/eliashaeussler/composer-update-check/compare/0.8.1...0.8.2
[0.8.1]: https://github.com/eliashaeussler/composer-update-check/compare/0.8.0...0.8.1
[0.8.0]: https://github.com/eliashaeussler/composer-update-check/compare/0.7.3...0.8.0
[0.7.3]: https://github.com/eliashaeussler/composer-update-check/compare/0.7.2...0.7.3
[0.7.2]: https://github.com/eliashaeussler/composer-update-check/compare/0.7.1...0.7.2
[0.7.1]: https://github.com/eliashaeussler/composer-update-check/compare/0.7.0...0.7.1
[0.7.0]: https://github.com/eliashaeussler/composer-update-check/compare/0.6.1...0.7.0
[0.6.1]: https://github.com/eliashaeussler/composer-update-check/compare/0.6.0...0.6.1
[0.6.0]: https://github.com/eliashaeussler/composer-update-check/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/eliashaeussler/composer-update-check/compare/0.4.4...0.5.0
[0.4.3]: https://github.com/eliashaeussler/composer-update-check/compare/0.4.0...0.4.3
[0.4.0]: https://github.com/eliashaeussler/composer-update-check/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/eliashaeussler/composer-update-check/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/eliashaeussler/composer-update-check/compare/0.1.3...0.2.0
[0.1.3]: https://github.com/eliashaeussler/composer-update-check/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/eliashaeussler/composer-update-check/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/eliashaeussler/composer-update-check/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/eliashaeussler/composer-update-check/tree/0.1.0
