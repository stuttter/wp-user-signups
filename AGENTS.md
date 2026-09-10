# WP User Signups contributor guidance

## Compatibility

- Preserve PHP 7.2 and WordPress 5.2 compatibility unless a dedicated pull
  request explicitly changes the published minimums.
- Treat signup activation, user creation, database schema, cache invalidation,
  metadata, multisite, capabilities, and notification hooks as critical code.
- Preserve public functions, hooks, filter arguments, class names, database
  columns, and public properties unless a deprecation path is part of the
  change.

## Tests

- Add a regression test before changing observed behavior.
- Test both single-site and multisite behavior when changing signup storage,
  activation, notification, queries, or administration.
- Keep activation-key and cache-invalidation regressions covered explicitly.
- Run `composer test`, the declared PHP syntax matrix, both real-WordPress
  integration modes, and metadata/artifact validation before requesting review.

## Automation

Follow the organization-level safety boundaries. AI-authored implementation
must remain a draft pull request and cannot modify workflows, release policy,
ownership, security policy, dependencies, or this file. Releases require the
protected `wordpress.org` environment and an exact commit from `master`.
