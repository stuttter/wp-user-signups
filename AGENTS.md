# WP User Signups contributor guidance

## Compatibility

- Preserve PHP 7.4 and WordPress 6.4 compatibility unless a dedicated pull
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

Follow the organization-level safety boundaries. Only a change in a centrally
preauthorized, mechanical, behavior-preserving class may be treated as an
AI-authored home-run and marked ready or merged without repeated approval. Its
exact head must be signed and GitHub-verified, every required check must be
green, and complete exact-head review must leave no unresolved actionable
finding.

Changes to workflows, release policy, ownership, security policy, dependencies,
or this file still require explicit human approval. So do runtime behavior,
user-visible behavior, stored or migrated data, authorization or security
boundaries, public APIs, and compatibility floors. Permission or secret-access
expansion, release publication, destructive operations, and unresolved behavior
risk are never home-runs. Release publication requires the protected
`wordpress.org` environment bound to an exact commit from `master`.
