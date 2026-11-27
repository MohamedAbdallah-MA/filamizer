# Publish a Filamizer release

The source lives in [MohamedAbdallah-MA/filamizer](https://github.com/MohamedAbdallah-MA/filamizer). Packagist tracks the repository under [filamizer/filamizer](https://packagist.org/packages/filamizer/filamizer).

## 1. Check the release candidate

Complete the [release checklist](RELEASING.md) and review the release diff. Check that the archive contains the MIT license, runtime assets, and installation docs. Keep local databases, generated demos, and dependencies out of the release.

## 2. Tag and push

From a reviewed commit with passing CI, create and push the version tag. The following example uses a beta tag.

```sh
git tag v0.1.0-beta.1
git push origin v0.1.0-beta.1
```

Composer derives the package version from the Git tag. Keep the version out of `composer.json`.

## 3. Verify the release in a clean host

Check that the tag appears on Packagist, then install it in a fresh Laravel application with Filament 5.

```sh
composer require "filamizer/filamizer:0.1.0-beta.1"
php artisan filamizer:install --migrate
```

Follow the [panel registration example](../README.md#2-register-the-plugin-and-grant-access) and verify that both editors open and save.

## 4. Submit or update the Filament listing

Use **Submit plugins** in the [Filament plugin directory](https://filamentphp.com/plugins). Include the Composer package name, compatibility, installation instructions, screenshots, and a support contact. If you maintain an older listing, update its installation instructions to use the current package name.
