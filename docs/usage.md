# Usage

## Command-line usage

```bash
composer update-check [options]
```

### `--ignore-packages` | `-i`

!!! info "since 0.1.0"

Ignores all listed packages when checking for available updates

Example:

```bash
composer update-check -i "my-vendor/*" -i "roave/security-advisories"
```

### `--no-dev`

!!! info "since 0.1.0"

Disables update check of require-dev packages

Example:

```bash
composer update-check --no-dev
```

### `--json` | `-j`

!!! info "since 0.2.0"

Formats output as JSON

Example:

```bash
composer update-check --json
```

### `--security-scan` | `-s`

!!! info "since 0.3.0"

Runs additional security scan for all outdated packages

Example:

```bash
composer update-check --security-scan
```

## Code usage

!!! warning
    The standalone PHP API was introduced in version 0.4.0.
    In case you're using a previous version, the API cannot be accessed directly.

```php
$updateChecker = new \EliasHaeussler\ComposerUpdateCheck\UpdateChecker($composer, $input, $output);
$updateCheckResult = $updateChecker->run($packageBlacklist, $includeDevPackages);
```

### `UpdateChecker::__construct()`

Initializes a new `UpdateChecker` object for the given Composer installation

| Parameter | Description |
| --------- | ----------- |
| `Composer $composer` | A `Composer` object defining the Composer installation to be checked. |
| `InputInterface $input = null` | An instance of `InputInterface` handling user input. |
| `OutputInterface $output = null` | An instance of `OutputInterface` for user-oriented output. To enable output of progress messages, set the verbosity to at least _verbose_. |

Example:

```php
new UpdateChecker($composer, $input, $output);
```

### `UpdateChecker::run()`

Runs the update check and returns an `UpdateCheckResult` object

| Parameter | Description |
| --------- | ----------- |
| `array $packageBlacklist = []` | List of packages to be excluded from update check |
| `bool $includeDevPackages = true` | Define whether dev-packages should be included in update check | 

Example:

```php
$packageBlacklist = [
    'my-vendor/*',
    'roave/security-advisories',
];
$updateChecker->run($packageBlacklist, false);
```

### `UpdateChecker::setSecurityScan()`

Defines whether to perform an additional security scan during update check

| Parameter | Description |
| --------- | ----------- |
| `bool $securityScan` | Define whether to perform security scan |

Example:

```php
$updateChecker->setSecurityScan(true);
```

### `UpdateChecker::getPackageBlacklist()`

Returns packages which were blacklisted (= ignored) during update check

Example:

```php
$blacklistedPackages = $updateChecker->getPackageBlacklist();
```
