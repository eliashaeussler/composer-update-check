# Usage

```bash
composer update-check [options]
```

The following options are available for the `update-check` command provided by this plugin.

## `--ignore-packages` | `-i`

Ignores all listed packages when checking for available updates

| Required:           | :negative_squared_cross_mark: |
| ------------------- | ----------------------------- |
| Type:               | `string`                      |
| Default:            | –                             |
| Multiple allowed:   | :white_check_mark:            |
| Available since:    | 0.1.0                         |

Example:

```bash
composer update-check -i "my-vendor/*" -i "roave/security-advisories"
```

## `--no-dev`

Disables update check of require-dev packages

| Required:           | :negative_squared_cross_mark: |
| ------------------- | ----------------------------- |
| Type:               | `bool` (no value)             |
| Default:            | `false`                       |
| Multiple allowed:   | :negative_squared_cross_mark: |
| Available since:    | 0.1.0                         |

Example:

```bash
composer update-check --no-dev
```

## `--json` | `-j`

Formats all user-oriented output as JSON

| Required:           | :negative_squared_cross_mark: |
| ------------------- | ----------------------------- |
| Type:               | `bool` (no value)             |
| Default:            | `false`                       |
| Multiple allowed:   | :negative_squared_cross_mark: |
| Available since:    | 0.2.0                         |

Example:

```bash
composer update-check --json
```

## `--security-scan` | `-s`

Runs additional security scan for all outdated packages

| Required:           | :negative_squared_cross_mark: |
| ------------------- | ----------------------------- |
| Type:               | `bool` (no value)             |
| Default:            | `false`                       |
| Multiple allowed:   | :negative_squared_cross_mark: |
| Available since:    | 0.3.0                         |

Example:

```bash
composer update-check --security-scan
```
