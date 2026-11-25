# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 25 November 2026
### Added
- `db:trigger:remove --all` now asks for confirmation, with `--force` to skip it in non-interactive runs
- `--dry-run` option for `db:trigger:remove` to show which triggers would be removed
- Hint to recreate indexer triggers (`bin/magento indexer:set-mode schedule`) after removing them
- PHP 8.5 support

### Changed
- `db:trigger:remove` returns a failure exit code for an unknown trigger name or a failed drop
- Dropped PHP 8.1 support; composer.json now declares PHP, `magento/framework` and `symfony/console` constraints instead of a wildcard

### Security
- Fix SQL injection via unquoted trigger names in `DROP TRIGGER` and the database name in the trigger lookup
- Escape console formatter tags and control characters in trigger names shown in CLI output

## [1.0.1] - 12 February 2026
### Fixed
- Add trigger name arg for `db:trigger:remove` CLI
- Update README.md

## [1.0.0] - 15 January 2026
- Initial setup
