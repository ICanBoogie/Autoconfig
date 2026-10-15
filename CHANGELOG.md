# Changelog

## v7.0.0

### New Requirements

- PHP 8.4+

### New features

- Added the `autoboot` key to the `extra.icanboogie` section of `composer.json`. Packages use it to register `Vendor\Class::method` callables that `Application::boot()` invokes with the application, before `BootEvent` is emitted. They are exposed as `Autoconfig::$boot_callables`, in dependency order. See `docs/adr/0001-autoboot.md`.

### Deprecated Features

None

### Backward Incompatible Changes

None

### Other Changes

- Replaced PHP Code Sniffer with PHP-CS-Fixer.
- The license is BSD-3-Clause, as for the other ICanBoogie packages.
- Tooling updated: PHPUnit 13.4, PHPStan 2.3 at level max.
- PHPStan runs at level `max` on `src` and `tests`. `AutoconfigResolver` now throws an `UnexpectedValueException` when the autoconfig file, or a filter, doesn't return an `Autoconfig`.
- `composer.json` runs `Plugin::dump` on `post-autoload-dump`, so that the autoconfig file exists when developing the package.



## v6.0.0

Initial release.

The code is mostly migrated from `icanboogie/icanboogie`, but as a composer plugin.

<!--

## v5.x to v6.0

### New Requirements

None

### New features

None

### Backward Incompatible Changes

None

### Deprecated Features

None

### Other Changes

None

-->
