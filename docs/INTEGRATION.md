# Configure and integrate Filamizer

Start with the [README quick start](../README.md#quick-start) for standard installs.

## Install from a local checkout

Use this when you’re developing Filamizer or testing local changes in a host application. Add the following entry to the host’s `composer.json`. Adjust `../filamizer` to point from the host project to your Filamizer checkout. If `repositories` already exists, add the entry to that list.

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../filamizer",
      "options": {
        "symlink": true,
        "versions": {
          "filamizer/filamizer": "0.1.0"
        }
      }
    }
  ]
}
```

Install and initialize the package in the host application.

```sh
composer require "filamizer/filamizer:0.1.0"
php artisan filamizer:install --migrate
```

The `0.1.0` version is the local Composer alias from the example. Register the plugin using the [panel provider example](../README.md#2-register-the-plugin-and-grant-access).

## Installer options and updates

Run installer commands from the host application. Run `composer update filamizer/filamizer`, then `php artisan filamizer:install --migrate` to run pending Filamizer migrations and republish assets. Repeat installation keeps stored themes and profiles.

| Option | Behavior |
| --- | --- |
| `--migrate` | Runs the package migrations without an interactive prompt. |
| `--no-interaction` | Skips questions. Pass `--migrate` too if the job should migrate the database. |
| `--no-assets` | Skips `filament:assets`. Use this when assets are published elsewhere in your workflow. |
| `--force` | Allows the installer’s migration in production and **overwrites published Filamizer config**. Save a copy of a customized `config/filamizer.php` before using this flag. |

Compare your published config with the package’s [default config](../config/filamizer.php) after an upgrade. Keep your local choices and merge any new nested options that your published copy is missing.

## Configuration reference

The installer publishes the file to `config/filamizer.php` in the host.

| Key | Use |
| --- | --- |
| `themes` | Define the palettes shown in **Starting palette**. |
| `fonts.arabic` and `fonts.latin` | Set the available font families. The panel requests configured families from Google Fonts. |
| `navigation.locales` | Choose the languages offered in the sidebar label editor. The default is `['en', 'ar']`. |
| `backdrop_disk` and `backdrop_directory` | Choose where shared backdrop images are stored. The defaults are the `public` disk and the `filamizer` directory. The panel needs a browser-accessible URL for these files. |
| `profiles.max_per_user` and `profiles.name_max_length` | Set the personal profile limit and name length. The defaults are 20 profiles and 40 characters. |
| `defaults` | Set initial appearance values and runtime fallbacks. Use the editor to change settings already stored in the database. |

## Control which editors appear

| Panel option | Effect |
| --- | --- |
| `canCustomize($callback)` | Grants access to shared appearance editing when the callback returns true. Use Filament’s auth guard in the callback. |
| `settingsPage(false)` | Omits the standalone **Dashboard appearance** page. Use it when embedding the tabs in a host page. |
| `personalAppearance(false)` | Removes **My appearance** and its user-menu entry for the panel. Saved overrides can still apply. Clear the shared list of allowed user skins to suspend the overrides across the application. |

The registration options are per panel. Settings, sidebar layout, profiles, and preferences are stored application-wide.

## Embed the tabs in a host settings page

Keep your existing `Filament\Pages\SettingsPage` and its settings class. Set `settingsPage(false)` on the plugin and keep its authorization callback. Add `Filamizer\Concerns\InteractsWithStudioSettings` to your page and include `Filamizer\Filament\StudioAppearanceTab::make()` and `Filamizer\Filament\StudioSidebarTab::make()` in your existing `Tabs` component.

Add these hooks to the settings page. If your page already implements them, merge the calls into the existing hooks.

```php
protected function mutateFormDataBeforeFill(array $data): array
{
    return $this->fillStudioData($data);
}

protected function mutateFormDataBeforeSave(array $data): array
{
    return $this->saveStudioData($data);
}

protected function afterSave(): void
{
    $this->reloadIfStudioSettingsChanged();
}
```

`fillStudioData()` loads the shared settings into the editor. `saveStudioData()` checks the plugin’s permission, saves the shared settings when allowed, and removes the Filamizer fields from the data passed to your host settings class.
