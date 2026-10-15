# 1. Autoboot: packages register callables invoked by `Application::boot()`

- Status: Proposed
- Date: 2026-10-15

## Context

`Application::boot()` hard-codes the setup of one package:

```php
Binding\Prototype\AutoConfig::configure($this);

emit(new BootEvent($this));
```

- `icanboogie` knows `bind-prototype` by name. Any other package needing setup at boot has to use
  `BootEvent`, which needs an `event.php` config and fires after boot.
- Prototype can't use `BootEvent`: it must be bound before anything calls a prototype method,
  including event handlers.
- Packages already register low-level configuration (config paths, config builders, locale paths)
  through Autoconfig, a Composer plugin that writes `vendor/icanboogie/autoconfig.php`. Boot setup is
  the same kind of information.

## Decision

Packages register boot callables in the `extra.icanboogie` section of their `composer.json`:

```json
{
    "autoboot": "ICanBoogie\\Binding\\Prototype\\Boot::boot"
}
```


- **Registration:** the Autoconfig plugin collects the values at `post-autoload-dump` and writes them
  into the generated file as a new `boot_callables` property of `Autoconfig`
  (`array<callable-string>`, defaulting to `[]`).
- **Form:** static `Vendor\Class::method` strings, validated against the schema and a pattern at dump
  time, and with `is_callable()` at boot.
- **Invocation:** `Application::boot()` calls each callable with the `Application`, after the
  constructor (config, container and events exist) and before `BootEvent` is emitted. Return values
  are ignored and exceptions propagate.
- **Order:** dependency order, as sorted by the plugin's `PackageMapSorter`; array order within a
  package. No weights.
- **Override:** only through `autoconfig-filters`, which can return
  `$autoconfig->with(['boot_callables' => ...])`.
- **First use:** `bind-prototype` provides `Boot::boot(Application $app)` and drops `AutoConfig`. The
  hard-coded call leaves `Application::boot()`.

`BootEvent` keeps its role: the extension point for application code reacting to a booted
application. Autoboot is for packages wiring themselves in.

## Alternatives considered

- **Hard-coded calls in `boot()`:** `icanboogie` must know every binding.
- **`BootEvent` only:** too late for Prototype, and needs an `event.php` per package.
- **An earlier event:** events depend on the very wiring being done.
- **Interface discovery (`Bootable`):** needs class scanning or a registry; Composer metadata is
  already one.
- **Tagged container services:** a lookup per boot for something known at dump time.
- **Autoconfig extension:** build-time, not runtime.
- **`AppConfig` option** (`AppConfigBuilder::add_boot_callable()`, set from each package's
  `config/app.php`):

  |                           | AppConfig                                          | Autoconfig (chosen)                   |
  |---------------------------|----------------------------------------------------|---------------------------------------|
  | Runtime cost              | Fragments merged (cheap only with `cache_configs`) | None, a literal in the generated file |
  | Ordering                  | Config weight, not dependency-aware                | Dependency order                      |
  | Callable forms            | Closures, arrays, strings                          | `Class::method` strings               |
  | Override by the app       | Natural, later fragments win                       | Only via `autoconfig-filters`         |
  | Per-package overhead      | An extra `config/app.php`                          | One `composer.json` key               |
  | Before config is resolved | No                                                 | Yes                                   |

  `AppConfig` is a good fit for application-level, overridable hooks, which this is not.

## Consequences

- `icanboogie` no longer references any binding in `boot()`; new packages self-register.
- The `autoconfig` package changes: schema, `SchemaOptions`, `Autoconfig`, generator. The generated
  file gains a `boot_callables` argument, defaulted so files generated earlier keep working until the
  next `composer dump-autoload`.
- A mistyped callable is only detected at boot, not at dump time, since the class may not be
  autoloadable from the plugin. Boot fails with an explicit `InvalidState`.
- Callables run in a fixed, dependency-based order. Needing finer control later means adding an
  object form (`{"callable": ..., "weight": ...}`) without breaking the string form.
- Overriding a package's boot callable is deliberately awkward; an application-level mechanism, if
  needed, would be a separate `AppConfig` option.
- `bind-prototype` loses `AutoConfig` (a breaking change, acceptable in v7), which also removes the
  name clash with `Autoconfig`.
- Other `BootEvent` listeners that are really package self-wiring (e.g. `bind-activerecord`) can move
  to autoboot later, package by package.
