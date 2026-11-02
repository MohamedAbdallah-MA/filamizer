# Filamizer architecture

This repository is a reusable Filament 5 panel plugin. A consuming Laravel
application supplies its panel, authenticated user, database and authorization.
It has no dependency on a demonstration application's source code or data.

## Entry points

- `FilamizerServiceProvider` registers configuration, migrations,
  translations, Blade views, scoped services, runtime CSS and the install command.
- `FilamizerPlugin` registers global and personal appearance pages, render hooks,
  navigation integration and panel-specific configuration callbacks.
- `ManageAppearance` edits shared appearance and sidebar settings. Access requires
  an explicit `canCustomize()` callback and is checked again when saving.
- `MyAppearance` edits personal settings through the current panel's auth guard.

## Data and appearance

Spatie Laravel Settings stores shared settings in its default database repository.
Package migrations add settings, user preferences and saved profiles. Installation
runs only package/dependency migrations. Repeated installation retains saved data.

`StudioTheme` resolves shared defaults, per-user overrides, and separate light/dark
values. `StudioPreferences` handles user preference lookup. Presets are optional
starting points; the generic default is Royal Indigo. Five skins are available:
Classic, Liquid Glass, Neon, Soft and Minimal. Profiles are part of the plugin.

Blade views and the shipped CSS render effects and previews without a consumer
asset build. Automatic glass readability retains raw choices while deriving
legible effective colors. Users may disable it separately for each mode.

Navigation customization changes presentation only. Application authorization
must still protect resources and actions independently.

## Boundaries and verification

Runtime settings and profiles are application-wide, including across panels.
Integer user IDs in one identity space are currently required. UUID/ULID owners,
tenant-isolated storage, overlapping identities and custom settings repositories
are not supported. Google Fonts requires network access and appropriate CSP.

Tests run under Orchestra Testbench with isolated SQLite storage and a plain user
model. The consuming application is not needed. See README for setup and
RELEASING.md for unverified compatibility and browser coverage.
