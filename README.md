# Filamizer

Customize a Filament panel at runtime, without compiling a custom theme: independent
light/dark looks, five skins, animated backgrounds, fonts, sidebar layout, saved
profiles, and personal appearance overrides.

**Pre-release:** an independent Filament 5 panel plugin. No host application,
application models, roles package, business data, or branded presets are bundled.
The working Composer identity is `filamizer/filamizer` and the
PHP namespace is `Filamizer`. The vendor name must be available and owned
by the publisher before publication. This is not an official Filament product.

## Requirements

- PHP 8.2 or later, Filament 5, and its supported Laravel version.
- Spatie Laravel Settings with its default **database** repository and `settings`
  table. The installer can publish and run that dependency's storage migration.
- Integer user IDs. User preferences and profile ownership currently share one
  application-wide user identity space; separate models with overlapping IDs,
  UUID/ULID owners, tenant-specific settings and panel-isolated storage are not
  supported yet.

The standalone suite currently tests Laravel 12 / Filament 5 with a plain Eloquent
user model and no roles package. Additional Laravel versions need release testing.

## Installation

The following is the intended distribution flow once the package is published:

```sh
composer require filamizer/filamizer
php artisan filamizer:install --migrate
```

During local development, add this directory as a Composer `path` repository in
your application and require `filamizer/filamizer:^0.1`. For an untagged local
checkout, set the path repository's `options.versions.filamizer/filamizer` to
`0.1.0`; release versions will come from repository tags.

Register the plugin in your panel provider and explicitly authorize the people who
may change the shared appearance. The permission name below is an example: define
it with your own Laravel Gate or replace the closure with your application's rule.

```php
use Filamizer\FilamizerPlugin;
use Filament\Facades\Filament;

$panel->plugin(
    FilamizerPlugin::make()
        ->canCustomize(fn (): bool =>
            Filament::auth()->user()?->can('manage-panel-appearance') ?? false
        ),
);
```

The package registers **Dashboard appearance** at `/your-panel/filamizer` and **My
appearance** in the user menu. Global editing is denied until `canCustomize()` is
configured; there is no assumed `super_admin` role or host user method. Personal
appearance is available to signed-in panel users while allowed skins are enabled.

`--migrate` runs Studio's migrations and, when needed, Spatie's repository migration.
It does not run unrelated pending application migrations. Existing values and
profiles are retained on repeat installation. Asset publication uses Filament's
standard `filament:assets` command, which publishes registered Filament assets.

- `--no-assets`: skip asset publication.
- `--force`: overwrite the published Studio config and allow migration in production.
- `--no-interaction` without `--migrate`: publish configuration/assets, skip migrations.

After updating the package, run the installer with `--migrate` again. Do not use
`--force` to overwrite a customized config unless you intend to replace it.

## Shared defaults and personal looks

The global page includes appearance and sidebar tabs. Light and dark skins,
foreground colors, backgrounds and effects are independent. Full customization
reveals precise color and effect controls; quick setup keeps the everyday choices
short. Profiles remain available for shared and personal looks.

Users can save their own appearance without changing the shared defaults. A shared
profile is maintained by global editors; a personal profile belongs to its user.
Hiding a navigation item is cosmetic and does not change authorization.

For Liquid Glass, automatic readability can adjust effective foregrounds and glass
strength while retaining selected raw values. Turn it off per mode to use exact
colors/opacity. Native inputs, including date fields, use the chosen border color.
Refraction is currently Chromium-specific and is not simulated by the miniature.

Google Fonts are requested for configured font families. Applications with strict
CSP or offline requirements need to account for those external styles/font requests.

## Optional integration

Disable the standalone global page if you already embed Studio in a settings page:

```php
FilamizerPlugin::make()
    ->settingsPage(false)
    ->canCustomize(fn (): bool => /* your authorization rule */ false);
```

For an existing `Filament\Pages\SettingsPage`, use
`Filamizer\Concerns\InteractsWithStudioSettings`. Add
`StudioAppearanceTab::make()` and `StudioSidebarTab::make()` from the package's
`Filament` namespace to your tabs. Call `fillStudioData($data)` from
`mutateFormDataBeforeFill()`, `saveStudioData($data)` from
`mutateFormDataBeforeSave()`, and `reloadIfStudioSettingsChanged()` from `afterSave()`.

`->personalAppearance(false)` disables the personal page and user-menu entry.
It does not delete or disable existing saved overrides; to disable personalization
entirely, empty the allowed user skins in the shared appearance settings.
These plugin options are per panel. Stored global settings, sidebar layouts,
preferences and profiles are currently shared across panels in the same application.

## Development

From a source checkout of this package directory, independently of any host application:

```sh
composer install
composer test
```

The tests use Orchestra Testbench and an in-memory SQLite database. They install
the package, exercise both Livewire editors, check panel-guard ownership and access,
verify repeat installation and keep unrelated application migrations pending.

The included CI workflow runs directly in this package repository. PHP
8.2/8.3/8.4 are configured; only PHP 8.4 has been verified locally so far.

The default preset is Royal Indigo. Both the branded gold preset and warm neutral
palette have been removed. Light and dark modes remain independently customizable.
Internal `Studio*` classes, `studio_*` tables, the settings group and CSS variables
are implementation identifiers; they do not depend on a particular application.

For a local checkout, use this repository entry in the consuming application's
`composer.json` (adjust the path for your machine):

```json
{
  "repositories": [{
    "type": "path",
    "url": "../filamizer",
    "options": {
      "symlink": true,
      "versions": {"filamizer/filamizer": "0.1.0"}
    }
  }]
}
```

Then run `composer require filamizer/filamizer:^0.1` and the
installer above. The Composer require command without this repository will work
only after publication, or after configuring another distribution repository.

See [uploading](docs/UPLOADING.md) for repository and directory submission steps.
See [renaming](docs/RENAMING.md) when replacing an earlier development checkout.
See [release preparation](docs/RELEASING.md) for the remaining publication work.

## License

Filamizer is licensed under the [MIT License](LICENSE).
