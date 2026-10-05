# contenir/contenir-asset-laminas-mvc

[![Continuous Integration](https://github.com/contenir/contenir-asset-laminas-mvc/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-asset-laminas-mvc/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-asset-laminas-mvc/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-asset-laminas-mvc)

Laminas MVC module for Contenir assets: keyed, profile-driven responsive image
variants (including WebP and AVIF) on top of
[contenir/storage](https://github.com/contenir/storage).

A template names one profile (for example `'card'`) and gets the whole
responsive set: srcset ladder, `sizes` attribute and `<picture>` sources in
extra formats. The profiles are the same `storage.variants` declarations the
CMS and `contenir/storage` read, so there is one source of truth.

- **View helpers:** `storageSrcSet()`, `storageSizes()`, `storageSources()` and `storageUrl()`.
- **On-demand variants:** a route and controller that generate a missing local
  variant on first request, and a secret-guarded endpoint an edge worker calls
  to generate a missing S3/R2 sibling.
- **CLI:** `storage:variants` to audit and backfill a backend.

## Requirements

- PHP 8.3, 8.4 or 8.5
- `contenir/storage` 2.x (use contenir-asset-laminas-mvc 2.0 for storage 0.6)
- laminas-mvc 3.8+, laminas-view 2, symfony/console 6.4.10+ or 7.1.3+, laminas-router 3, laminas-http 2, laminas-cli 1.8+
- ImageMagick (imagick extension or `magick`/`convert` CLI) for local generation

## Installation

```bash
composer require contenir/contenir-asset-laminas-mvc
```

With `laminas/laminas-component-installer` the module is registered for you;
otherwise add `Contenir\Asset\Laminas\Mvc` to `config/modules.config.php`.

## Configuration

Everything lives under the `storage` key that `contenir/storage` reads:

```php
'storage' => [
    'backend' => [
        'local' => ['type' => 'local', 'root_path' => 'public', 'public_path' => ''],
        // or an S3/R2 primary: ['type' => 's3', 'default' => true, 'publicUrl' => 'https://cdn…', 'generate_secret' => '…', …]
    ],
    'variants' => [
        'admin-thumb' => ['width' => 180, 'height' => 180, 'fit' => 'contain'],
        'card'        => ['dimensions' => ['320x', '640x', '960x'], 'sizes' => '(min-width: 768px) 33vw, 100vw', 'formats' => ['avif', 'webp']],
    ],
],
```

The primary backend decides the URL scheme (`_variant/<name>/` locally,
`<key>__<name>.<ext>` siblings on S3/R2). See [docs/configuration.md](docs/configuration.md).

## Usage in templates

```php
<picture>
    <?= $this->storageSources($asset->path, 'card') ?>
    <img srcset="<?= $this->storageSrcSet($asset->path, 'card') ?>"
         sizes="<?= $this->storageSizes('card') ?>"
         src="<?= $this->storageUrl($asset->path, 'card-320') ?>"
         alt="">
</picture>
```

## Documentation

- [Configuration](docs/configuration.md)
- [View helpers](docs/view-helpers.md)
- [Serving and generating variants](docs/variant-serving.md)
- [The storage:variants command](docs/cli.md)

## Development

The QA toolchain is [php-db/phpdb-qa-tools](https://github.com/php-db/phpdb-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed
separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: no I/O, collaborators doubled
composer test-integration  # integration suite: real files, ImageMagick and Laminas containers
composer test-coverage     # both suites, clover.xml for Codecov
```

## License

MIT. See [LICENSE](LICENSE).
