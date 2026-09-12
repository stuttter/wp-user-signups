# Contributing

Thanks for helping maintain WP User Signups.

## Before changing behavior

Describe the observable behavior, compatibility expectations, and acceptance
criteria in a GitHub issue. Security reports belong in the private reporting
channel described in `SECURITY.md`.

## Pull requests

- Keep each pull request focused and reversible.
- Add regression coverage for behavior changes and bug fixes.
- Preserve the declared PHP and WordPress minimum versions.
- Test single-site and multisite behavior when the affected code supports both.
- Identify database changes, destructive behavior, new dependencies, and
  release-process changes explicitly.
- Do not commit credentials, build caches, development databases, or generated
  release ZIP files.
- Wait for every required check and resolve review conversations before merge.

AI-assisted contributions are welcome, but the contributor remains responsible
for understanding and validating the result.

## Development requirements

The plugin and its Composer development toolchain require PHP 7.4 or newer.
Production Composer installs should omit development dependencies.
