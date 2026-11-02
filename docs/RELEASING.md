# Release checklist

This is an unpublished independent Filamizer package.

1. Finalize the Composer vendor/name, repository, support contact and license.
   The current generic vendor is a working identity, not a reserved Packagist name.
2. Run `composer validate --strict`, `composer install`, and `composer test`.
   Run the PHP 8.2/8.3/8.4 CI matrix and verify the advertised Laravel/Filament range.
3. Verify a fresh generic Laravel/Filament host in a real browser: every skin,
   light/dark independently, profiles, RTL/LTR, small screens, auth, validation,
   placeholder contrast, navigation, date inputs and popovers. Testbench does not
   replace this browser matrix.
4. Review panel-scoped asset registration against Filament's getting-started
   guide; CSS registration currently occurs in the service provider. Verify
   behavior when multiple panels use different plugin configurations.
5. Keep documented limits explicit: integer owners, one user identity space,
   application-wide settings/profiles, default Spatie database repository.
6. Check other navigation/theme plugins, keyboard sidebar reordering,
   CSP/offline font behavior and preview fidelity for refraction. These remain
   compatibility or implementation tasks to resolve before stable release.
7. Prepare screenshots, a generic demo, install instructions and support details.
   Run dependency audit, review the archive and ensure it contains no credentials,
   development dependencies, local databases or host application files.
8. Tag a release only after the checks pass; distribute through the chosen
   Composer repository and submit the listing separately to Filament.

See UPLOADING.md for commands. This checklist does not claim release approval.

References:
- https://filamentphp.com/docs/5.x/plugins/getting-started
- https://filamentphp.com/docs/5.x/plugins/panel-plugins
- https://filamentphp.com/plugins
