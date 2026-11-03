# Security policy

## Supported versions

Filamizer is currently a pre-release package for Filament 5. Security fixes are
made on the latest maintained `main` revision and included in subsequent releases.
Older development snapshots are not maintained separately. Update to the latest
revision or release before checking whether a problem is already resolved.

## Reporting a vulnerability

Please report vulnerabilities privately using GitHub's **Report a vulnerability**
feature in this repository's Security tab:

https://github.com/MohamedAbdallah-MA/filamizer/security/advisories/new

Do not publish exploit details, credentials, personal data, or production database
contents in public issues or pull requests. If private reporting is unavailable,
use a contact method published on the maintainer's GitHub profile to request a
private reporting channel; do not include exploit details in a public message.

Include the affected package revision/version, PHP/Laravel/Filament versions,
reproduction steps, expected versus actual behavior, and potential impact. Use
synthetic data and redact secrets. Reports will be reviewed and fixes coordinated
with the reporter where possible. Do not test systems you do not own or have
permission to assess. This policy makes no response-time or bounty guarantee.

## Deployment and maintenance

- Configure `FilamizerPlugin::canCustomize()` with an application authorization
  rule. Shared appearance editing is denied without it.
- Sidebar visibility changes are presentation only. Enforce access through your
  application's policies and permissions, including on resource actions.
- Keep the host application and its dependencies patched. Run `composer audit`
  against the consuming application's dependency resolution as well as this package.
- Respect the documented single-identity, integer-user-ID and shared-storage
  limits. The package does not provide tenant or panel data isolation.

CI pins actions to full commit hashes, uses a read-only repository token without
persisting checkout credentials, and audits resolved dependencies. Dependabot
checks Composer and Actions updates weekly with a seven-day version-update
cooldown; GitHub security updates are not delayed by that cooldown. Updates still
require review and passing checks before merge. These controls reduce risk but do
not constitute a complete security audit or guarantee.
