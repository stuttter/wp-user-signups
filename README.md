# WP User Signups

[![CI](https://github.com/stuttter/wp-user-signups/actions/workflows/ci.yml/badge.svg)](https://github.com/stuttter/wp-user-signups/actions/workflows/ci.yml)

This is the best way to manage user (and site) sign-ups in WordPress.

Use it to:

* View & manage sign-ups the WordPress way
* Resend activation emails to users who haven't received theirs yet
* Manually edit & activate sign-ups for users who are having trouble

## Installation

* Download and install using the built in WordPress plugin installer.
* Activate in the "Plugins" area of your network admin by clicking the "Activate" link.
* Consider sponsoring future development by clicking "Sponsor".
* No further setup or configuration is necessary.

## FAQ

### Does this work on single-site, multisite, and multi-network installations?

Yes. Yes. Yes.

### Does this work with BuddyPress, bbPress, and GlotPress?

Yes. Yes. Yes.

### Does this work with other membership plugins?

Ya know, I'm not really sure. Please test it with your favorite ones and let me know!

### Credits

This plugin is largely inspired by:

* BuddyPress
* Unconfirmed

### Where can I get support?

This plugin is free for anyone to use.

* [Community support](https://wordpress.org/support/plugin/wp-user-signups/)
* [Development discussions](https://github.com/stuttter/wp-user-signups/discussions)

## Development

Install the locked development tools and run the unit suite:

```sh
composer install
composer test
```

Pull requests also run syntax checks across the supported PHP matrix and smoke
tests on WordPress 6.4 and the latest stable WordPress release in both
single-site and multisite configurations.

## Contributing

Please [open an issue](https://github.com/stuttter/wp-user-signups/issues/new/choose)
before beginning a substantial change, then follow [CONTRIBUTING.md](CONTRIBUTING.md).
