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

Follow the organization-level safety boundaries. Routine AI-authored changes
may be marked ready and merged without repeated approval only when the exact
head is signed and GitHub-verified, every required check is green, and complete
exact-head review leaves no unresolved actionable finding. The change must not
broaden permissions or secret access, publish a release, introduce unresolved
behavior risk, or perform a destructive operation.

Changes to workflows, release policy, ownership, security policy, dependencies,
or this file still require explicit human approval. Release publication remains
a human decision and requires the protected `wordpress.org` environment bound
to an exact commit from `master`.
