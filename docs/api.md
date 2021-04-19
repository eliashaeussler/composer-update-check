# API

!!! important
    The standalone PHP API was introduced in version 0.4.0.
    In case you're using a previous version, the API cannot be accessed directly.

## `UpdateChecker::__construct()`

Initializes a new [`UpdateChecker`]({{ repository.blob }}/src/UpdateChecker.php)
object for the given Composer installation

| Parameter                         | Description                                                                    |
| --------------------------------- | ------------------------------------------------------------------------------ |
| `Composer $composer`              | A `Composer` object which describes the Composer installation to be checked.   |
| [`OutputBehavior`][1]` $behavior` | The constructed `OutputBehavior` object which describes behavior of user-oriented output.  |
| [`Options`][2]` $options`         | Transfer object of all resolved options to be configured for the update check. |

[1]: {{ repository.blob }}/src/IO/OutputBehavior.php
[2]: {{ repository.blob }}/src/Options.php

```php
$behavior = new OutputBehavior(
    new Style(Style::JSON),
    new Verbosity(Verbosity::VERBOSE),
    $composer->getIO()
);
$options = new Options();
$options->setIncludeDevPackages(false);

new UpdateChecker($composer, $behavior, $options);
```

## `UpdateChecker::run()`

Runs the update check and returns an
[`UpdateCheckResult`]({{ repository.blob }}/src/Package/UpdateCheckResult.php) object

Example:

```php
$packageBlacklist = [
    'my-vendor/*',
    'roave/security-advisories',
];
$updateChecker->run($packageBlacklist, false);
```

## `UpdateChecker::getPackageBlacklist()`

Returns packages which were blacklisted (= ignored) during update check

Example:

```php
$blacklistedPackages = $updateChecker->getPackageBlacklist();
```
