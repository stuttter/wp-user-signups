# Security policy

## Reporting a vulnerability

Do not open a public issue for a suspected vulnerability. Use GitHub private
vulnerability reporting for this repository. If that is unavailable, contact
the repository owner privately through their GitHub profile.

Include the affected version, security impact, minimum reproduction, required
WordPress configuration, and sanitized evidence. Never include credentials,
personal information, or production database contents.

## Supported versions

Security fixes are normally applied to the current release line. Older releases
may be unsupported when WordPress or PHP compatibility makes a safe backport
impractical.

## Automation and releases

GitHub Actions, dependency compromise, WordPress.org credentials, release tags,
generated artifacts, and unauthorized publication are security-sensitive.
Release credentials are limited to protected deployment jobs and are never
available to pull request or AI implementation code.
