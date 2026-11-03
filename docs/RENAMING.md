# Filamizer rename

The package now uses `filamizer/filamizer`, the `Filamizer` PHP namespace,
`FilamizerPlugin`, `FilamizerServiceProvider`, `filamizer.php` configuration,
`filamizer:install`, and the `filamizer` plugin ID and Blade view namespace.
The shared editor is available at `/your-panel/filamizer`.

## Updating a development installation

1. Point the application's Composer path repository to the new `filamizer` folder
   and update its package requirement/version mapping to the name above. Remove
   the former package requirement when replacing it; do not install both together.
2. Update PHP imports and panel registration to
   `Filamizer\FilamizerPlugin::make()->canCustomize(...)`.
3. If configuration was published under the former package name, copy its intended
   custom values into `config/filamizer.php`. Update custom view overrides and
   direct plugin/config lookups to the new names as applicable.
4. Run `composer update filamizer/filamizer`, clear application caches,
   and run `php artisan filamizer:install --migrate` to publish the new asset path
   and install any missing package storage. Avoid `--force` unless deliberately
   replacing published configuration.

Existing `studio_*` tables, the `studio` settings group, migration filenames,
stored profile data and CSS/form identifiers are intentionally retained. The
rename does not reset shared settings, personal preferences or saved profiles.
Existing stored background-image paths are not rewritten; new uploads use the
configured directory. Public PHP names have changed; this pre-release rename
does not provide class aliases for the former namespace.

Git history remains intact. Historical commits retain their original names.
The original local package folder remains a backup because Windows held it open;
continue new development in the `filamizer` folder.
