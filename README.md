# Filamizer

Edit the look of a Filament 5 panel from the panel itself. Set shared light and dark themes, choose Arabic and English fonts, rearrange the sidebar, and let users save a personal look. Filamizer ships its CSS ready to use.

[Quick start](#quick-start) · [Using the editors](docs/USAGE.md) · [Configuration and integration](docs/INTEGRATION.md)

## Requirements

- PHP 8.2+ and an existing Filament 5 panel. The package test suite uses Laravel 12.
- The default Spatie Laravel Settings database repository and its `settings` table. The installer creates the table if needed.
- One application-wide identity space with integer user IDs. Shared appearance and sidebar layouts apply across panels that use Filamizer. Panel and tenant isolation, UUID/ULID owners, and separate user models with overlapping IDs are currently unsupported.

## Quick start

### 1. Install in your Laravel application

Filamizer is available on Packagist as a development version. [Packagist releases](https://packagist.org/packages/filamizer/filamizer)

```sh
composer require "filamizer/filamizer:dev-main"
php artisan filamizer:install --migrate
```

The installer publishes `config/filamizer.php` and Filament assets. With `--migrate`, it runs Filamizer’s migrations and the Spatie storage migration if needed. Unrelated application migrations stay pending.

### 2. Register the plugin and grant access

In your panel provider, usually `app/Providers/Filament/AdminPanelProvider.php`, import these classes and add the plugin to your panel configuration.

```php
use Filamizer\FilamizerPlugin;
use Filament\Facades\Filament;

// Add inside panel(), where $panel is your Filament panel.
$panel->plugin(
    FilamizerPlugin::make()
        ->canCustomize(fn (): bool =>
            Filament::auth()->user()?->can('manage-panel-appearance') ?? false
        ),
);
```

Define `manage-panel-appearance` as a Laravel Gate in your application, or substitute a permission your application already uses. The callback controls access to shared editing. Filamizer denies shared editing by default.

### 3. Open the editor

If your panel path is `admin`, sign in with permission to manage appearance and open **Dashboard appearance** in the sidebar or visit `/admin/filamizer`.

| Where | What you can change |
| --- | --- |
| **Dashboard appearance** → **Dashboard Appearance & Design System** | Shared light and dark looks, including palette, skin, backdrop, and typography |
| **Dashboard appearance** → **Sidebar** | Shared navigation groups, order, labels, icons, and visibility |
| User menu → **My appearance** | Your own look, using the skins the admin has enabled |

To confirm the install, change **Main color**, click **Save changes**, and open another page in the panel. The new color should appear there. [Editor walkthrough](docs/USAGE.md)

## Troubleshooting

| Symptom | What to check |
| --- | --- |
| **Dashboard appearance** is missing, or the URL returns 403 | Make sure the plugin is registered on this panel and the signed-in user passes `canCustomize()`. |
| **My appearance** is missing | Check that `personalAppearance()` is enabled on the panel and at least one skin is selected under **Dark mode and personal looks**. |
| The editor reports a missing table | Run `php artisan filamizer:install --migrate` in the host app and check its database connection. |
| The panel loads without Filamizer styling | Run `php artisan filament:assets` in the host app. |
| An uploaded backdrop is missing | With Laravel’s default local `public` disk, run `php artisan storage:link` and check that your web server serves the file from `public/storage`. |

## More documentation

- [Editor walkthrough](docs/USAGE.md) for changing themes, building the sidebar, and managing personal looks.
- [Configuration and integration](docs/INTEGRATION.md) for local checkouts, upgrades, installer flags, and embedding the editor.
- [Upgrading from the earlier package name](docs/RENAMING.md).
- [Release checks](docs/RELEASING.md) and [publishing a version](docs/UPLOADING.md) for maintainers.
- [Security policy](SECURITY.md) and [MIT license](LICENSE).

To run the package tests, run `composer install` and `composer test` from this repository. The tests use Testbench and in-memory SQLite.
