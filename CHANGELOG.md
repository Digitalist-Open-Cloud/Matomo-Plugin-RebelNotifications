# Changelog

## Unreleased

### Security

- HTML in a message is cleaned wherever it is saved (page, API, console) and again when it is shown: only the allowed tags are kept, without attributes, and a link keeps only a web or mail address.
- A message without raw HTML is shown as text on the manage page.
- `rebelnotifications:create` only enables a notification and raw HTML when `--enabled` and `--raw` are given.
- Changes on the manage page have to be posted.

### Added

- The API checks title, message, context, priority and type.
- Changes are announced with events, which RebelAuditLog logs.
- The table is removed when the plugin is uninstalled.
- PHPStan check, Dependabot and a security policy.

### Changed

- The menu item is shown to super users only.
- All texts of the pages are translated (English, Swedish, French).

## [5.0.8] - 2026-03-02

### Fixed

- Make sure integer values really are integers.

## [5.0.7] - 2026-03-01

### Security

- Added CSRF nonce protection

### Added

- More tests, to check for nonce.
- Added nonce field to create form, converted delete link to POST form
- Added nonce field to update form

### Changed

- Code improvements for `API.php`
- Removed duplicate option in `Commands/CreateNotification.php`


## [5.0.6] - 2026-02-27

### Security

- Added filter for HTML - setting allowed tags, so a user with Super admin access can not inject scripts.

## [5.0.4] - 2025-01-03

### Added

- Console commands to create and list notifications.
- Improved usability on the manage page for notifications.

## [5.0.3] - 2025-01-02

### Added

- Changelog :)
- Copied README.md to docs/index.md

## [5.0.2] - 2025-01-02

### Added

- Missing translation for menu.

### Changed

- More code adjustment to make both code and test work as designed.

## [5.0.1] - 2025-01-02

### Changed

- Some code fixes for tests.

## [5.0.0] - 2025-01-02

### Added

- First release

