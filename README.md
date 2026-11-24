# Check payment

## About

This module allows you to accept payments by check.

## Reporting issues

You can report issues with this module in the main PrestaShop repository. [Click here to report an issue][report-issue].

## Contributing

PrestaShop modules are open source extensions to the [PrestaShop e-commerce platform][prestashop]. Everyone is welcome and even encouraged to contribute with their own improvements!

Just make sure to follow our [contribution guidelines][contribution-guidelines].

## License

This module is released under the [Academic Free License 3.0][AFL-3.0]

[report-issue]: https://github.com/PrestaShop/PrestaShop/issues/new/choose
[prestashop]: https://www.prestashop.com/
[contribution-guidelines]: https://devdocs.prestashop.com/1.7/contribute/contribution-guidelines/project-modules/
[AFL-3.0]: https://opensource.org/licenses/AFL-3.0

## Development

```bash
composer install          # includes dev tools (PHPUnit)
vendor/bin/phpunit        # unit tests (tests/Unit), no PrestaShop needed
```

### Building the module zip for merchants

Always use the build script, never zip a working folder by hand:

```bash
bin/build-zip.sh          # or: bin/build-zip.sh <tag|branch|commit>
```

It exports a clean copy of the git ref (uncommitted changes are not included),
installs production dependencies only (`composer install --no-dev`), removes tests
and dev files, and writes `dist/paylands-<version>.zip` with a single `paylands/`
folder. It aborts if the zip would contain logs, tests, PHPUnit or IDE/OS files.
