# Uploading Filamizer

This folder is the package repository root. It contains source, tests and CI;
the consuming Laravel application is separate. Nothing has been published yet.

## 1. Prepare the identity and release

The working Composer name is `mohamedabdallah-ma/filamizer`, with
`Filamizer\FilamizerPlugin` as the plugin class. Composer requires a
vendor/package name. Check that you own the chosen vendor before publishing;
change `composer.json`, the asset package ID in the service provider, the asset
publication test and documentation together if you choose another vendor.

Filamizer is distributed under the MIT license. Keep the root `LICENSE` file and
its copyright notice in the distributed package.
Complete [the release checklist](RELEASING.md), then run:

```sh
composer install
composer validate --strict
composer test
composer audit
```

Do not upload `vendor`, caches, credentials, local databases or host application
files. The included ignore rules cover development dependencies and caches.

## 2. Upload source to GitHub

This folder already contains a Git repository with the package's extracted
development history and a standalone-conversion commit on `main`. Do not
reinitialize it or replace that history with a new initial commit.

The GitHub repository has been renamed to `MohamedAbdallah-MA/filamizer`.
The local origin points to that repository. Run these commands from this folder
using your authenticated Git client:

```sh
git status
git log --oneline
git remote set-url origin git@github.com:MohamedAbdallah-MA/filamizer.git
git push -u origin main
```

Review the history before making the repository public: earlier revisions retain
their original package identity and documentation. The current revision uses
Filamizer branding. Keep `composer.json` at repository root. Do not
upload the surrounding web projects directory. The GitHub Actions
workflow will run when pushed. Review its results before tagging a release.

After release checks pass, choose the correct version, for example a preview:

```sh
git tag v0.1.0-beta.1
git push origin v0.1.0-beta.1
```

Composer derives release versions from Git tags; do not add a hard-coded version
to the package's `composer.json`. A beta tag requires consumers to opt into beta
stability, for example `composer require mohamedabdallah-ma/filamizer:^0.1@beta`.

## 3. Make the package installable

For a public package, finalize its license, make the repository public, and submit
the repository URL at [Packagist](https://packagist.org/packages/submit). Configure
repository updates using Packagist's instructions. Verify a clean installation
from that repository before advertising an install command.

For private or paid distribution, configure an authenticated Composer repository
or private distribution service and provide customers with its installation and
credential instructions. The public Packagist service aggregates public packages;
listing a paid plugin on Filament does not itself distribute its private code.
Keep credentials out of source control and examples.

## 4. Submit the Filament listing

Open [Filament Plugins](https://filamentphp.com/plugins) and follow **Submit
plugins**. Prepare the package/repository URL, installation docs, Filament 5
compatibility details, screenshots, a generic demo, license or purchase details,
and a support contact. Follow the current author form for its required fields.
Repository upload, Composer distribution and directory submission are separate
steps; a submission does not guarantee approval.

Official references:

- [Filament plugin development](https://filamentphp.com/docs/5.x/plugins/getting-started)
- [Packagist submission and package naming](https://packagist.org/about)
- [Composer libraries and version tags](https://getcomposer.org/doc/02-libraries.md)
