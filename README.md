# Autoconfig

Configures [ICanBoogie][] automatically.

> [!WARNING]
> This is an internal ICanBoogie package and is not meant to be used separately.

Autoconfig is a Composer plugin. On `post-autoload-dump`, it collects the `extra.icanboogie` section of
the `composer.json` of every installed package (and of the root package) and writes the result to
`vendor/icanboogie/autoconfig.php`, which returns an `Autoconfig` instance.



## Options

Packages configure Autoconfig in the `extra.icanboogie` section of their `composer.json`. The section is
validated against [src/schema.json](src/schema.json).

| Option                  | Description                                                                   |
|-------------------------|-------------------------------------------------------------------------------|
| `config-path`           | A path to add to the config locations.                                        |
| `config-weight`         | The weight of the config paths. Defaults to `-100` (framework).               |
| `config-constructor`    | A map of config names to config builder classes.                              |
| `locale-path`           | A path, or list of paths, to add to the locale message locations.             |
| `autoconfig-filters`    | A callback, or list of callbacks, to alter the Autoconfig once generated.     |
| `autoboot`              | A callable, or list of callables, invoked with the application when it boots. |
| `app-path`, `app-paths` | The root directory, and hierarchical paths, of the application.               |

```json
{
    "extra": {
        "icanboogie": {
            "config-path": "config",
            "locale-path": "locale",
            "autoboot": "Acme\\MyPackage\\Boot::boot"
        }
    }
}
```



## Autoboot

`autoboot` lets a package set itself up when `Application::boot()` runs, without the application having to
know about it. Each value is a static `Vendor\Class::method` string:

```php
namespace Acme\MyPackage;

use ICanBoogie\Application;

final class Boot
{
    public static function boot(Application $app): void
    {
        // Wire the package into the application
    }
}
```

- Callables are invoked with the `Application` after it is constructed (config, container, and events are
  available) and before `BootEvent` is emitted. Return values are ignored and exceptions propagate.
- They are exposed as `Autoconfig::$boot_callables`, in dependency order, and in declaration order within a
  package.
- Values that don't match the `Vendor\Class::method` form make the dump fail with an
  `InvalidArgumentException`.
- To override them, use an `autoconfig-filters` callback returning
  `$autoconfig->with(['boot_callables' => [...]])`.

Use `BootEvent` instead for application code reacting to a booted application. See
[ADR 0001](docs/adr/0001-autoboot.md) for the rationale.



----------



## Continuous Integration

The project is continuously tested by [GitHub actions](https://github.com/ICanBoogie/Autoconfig/actions).

[![Tests](https://github.com/ICanBoogie/Autoconfig/actions/workflows/test.yml/badge.svg?branch=7.0)](https://github.com/ICanBoogie/Autoconfig/actions/workflows/test.yml)
[![Static Analysis](https://github.com/ICanBoogie/Autoconfig/actions/workflows/static-analysis.yml/badge.svg?branch=7.0)](https://github.com/ICanBoogie/Autoconfig/actions/workflows/static-analysis.yml)
[![Code Style](https://github.com/ICanBoogie/Autoconfig/actions/workflows/code-style.yml/badge.svg?branch=7.0)](https://github.com/ICanBoogie/Autoconfig/actions/workflows/code-style.yml)



## Code of Conduct

This project adheres to a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in
this project and its community, you're expected to uphold this code.



## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for details.



[ICanBoogie]: https://github.com/ICanBoogie/ICanBoogie
