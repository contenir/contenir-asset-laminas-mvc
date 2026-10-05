# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [2.0.0] - Unreleased

The public API is unchanged apart from the typing noted below. The major
version marks the move to PHP 8.3+ and the php-db QA toolchain shared by all
Contenir 2.x packages. See [UPGRADE-2.0.md](UPGRADE-2.0.md).

### Changed

- Requires PHP 8.3, 8.4 or 8.5, and accepts `contenir/storage` ^0.6.1 or ^2.0.
- Requires `psr/container` ^1.1 or ^2.0 (1.0 had no parameter types).
- Class constants are typed (`AssetUrlBuilder::BACKEND_LOCAL`,
  `ProfileProviderService::PREVIEW_VARIANT`).
- `AssetVariantController::indexAction()` returns a `Response` carrying the
  file instead of writing headers, streaming with `readfile()` and calling
  `exit`, so the MVC response cycle completes normally.
- Factories check the type of each service they fetch and fail with an
  `UnexpectedValueException` naming the service.
- Empty strings in the primary backend's `root_path`, `binary`,
  `public_path`, `publicUrl` and `public_base_url` count as unset.

### Fixed

- `VariantGenerator` accepted a URL-decoded folder containing `..`, which let
  a request read originals and write variants outside `<root>/asset/`. Such
  folders now 404.

### Added

- `docs/` pages, CI on PHP 8.3, 8.4 and 8.5 against lowest, locked and latest
  dependencies with Codecov, and unit/integration suites at 100% line coverage.

### Licence

- Still MIT. The copyright holder is now Contenir, and the permission notice
  restores the missing "USE OR OTHER" wording.

### Removed

- `squizlabs/php_codesniffer` and `phpcs.xml`, replaced by Mago via
  `php-db/phpdb-qa-tools`; the unused `league/flysystem-memory` dev dependency.

## [0.6.x] - 2026-08-25 to 2026-09-04

- `storage:variants` driven by the storage layer's own reporting and
  backfill; percent-encoded local URLs; `publicUrl` fallback; PHP 8.1 support.

## [0.5.x] - 2026-07-08 to 2026-08-21

- Backend settings read from the primary backend; render-time ownership guard
  dropped in favour of warnings on unknown profiles and variants.

## [0.4.x] - 2026-06-26 to 2026-08-21

- `contenir/storage` ^0.5 flat schema, path-ownership validation in helpers.

## [0.3.x] - 2026-06-24

- Unified art-directed profiles from `storage.variants`, dimension families in
  `storage:variants`.

## [0.2.x] - 2026-06-22 to 2026-06-24

- The `storage:variants` command (audit and backfill) and its fixes.

## [0.1.0] - 2026-06-10

- Initial release: keyed variant route and controller, profile provider and
  view helpers.
