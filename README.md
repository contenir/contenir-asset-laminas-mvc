# contenir/contenir-asset-laminas-mvc

Laminas MVC adapter for Contenir assets. Serves **on-demand responsive image
variants** — including **WebP** and **AVIF** — backed by
[`contenir/storage`](https://github.com/contenir/storage)'s `ImageResizer`.

Variants are **keyed by profile**: a template passes one profile key (e.g.
`'tile'`) and the responsive behaviour — widths, crop/scale, quality, the HTML
`sizes` attribute, and output formats — all comes from the shared
`settings.storage.profiles` config that the CMS reads too. No per-template width
lists, no hand-written `sizes`. Generated variant files share the
`_variant/<name>/` directory `contenir/storage` writes to, so the CMS-generated
renditions and the front-end's format siblings live together.

## What it provides

- **`AssetVariantController` + `assetvariant` route** — serves
  `/asset/<folder>/_variant/<name>/<filename>`. Existing variant files are served
  directly by the web server; only missing ones reach the controller, which
  resizes on demand (per the named variant's definition) and streams the result.
- **`ProfileProviderService`** — reads `settings.storage.profiles` and exposes
  typed `Contenir\Asset\Laminas\Mvc\Profile\Profile` / `Contenir\Storage\Variant`
  objects. Variant names are globally unique, so a bare name resolves to one
  definition.
- **View helpers** for templates:
  - `storageSrcSet($path, $profile)` — responsive `srcset` over the profile's
    variant ladder (source format).
  - `storageSizes($profile)` — the profile's configured `sizes` attribute.
  - `storageSources($path, $profile)` — `<source>` elements (one per profile
    `formats` entry, e.g. AVIF then WebP) for a `<picture>`.
  - `storageUrl($path, $variant = null, $format = null)` — a single URL (original,
    or a named `_variant/<name>/` variant, optionally in a given format).
- **`AssetUrlBuilder`** — the pure string URL builder behind the helpers. Returns
  **raw** URLs; escaping is the output context's job.

## URL scheme

```
/asset/<folder>/_variant/<name>/<file>.<fmt>
```

`<name>` is a profile's variant key (e.g. `tile-640`); `<fmt>` is the source
extension, `webp`, or `avif`. When the requested format cannot be produced (e.g.
the ImageMagick build lacks AVIF), the controller falls back to a source-format
variant so the URL returns valid image bytes rather than a 404.

## Installation

```bash
composer require contenir/contenir-asset-laminas-mvc
```

If you use `laminas/laminas-component-installer`, the module is registered
automatically; otherwise add `Contenir\Asset\Laminas\Mvc` to
`config/modules.config.php`.

## Configuration

This package reads **two** config keys:

1. **`settings.storage.profiles`** — the shared profile catalogue (also read by
   the CMS). Each variant carries its own width/height/fit/quality; add a `sizes`
   string and a `formats` list per profile for the front-end:

   ```php
   'settings' => [
       'storage' => [
           'profiles' => [
               'tile' => [
                   'type'       => 'local',
                   'rootPath'   => './public/asset/library',
                   'publicPath' => '/asset/library',
                   'sizes'      => '(min-width: 768px) 33vw, 100vw',
                   'formats'    => ['avif', 'webp'],
                   'variants'   => [
                       'admin-thumb' => ['width' => 180, 'height' => 180, 'fit' => 'contain'],
                       'tile-320'    => ['width' => 320, 'height' => 240, 'fit' => 'cover', 'quality' => 80],
                       'tile-640'    => ['width' => 640, 'height' => 480, 'fit' => 'cover', 'quality' => 80],
                   ],
               ],
           ],
       ],
   ],
   ```

   Variant names are **globally unique** across profiles (so `_variant/<name>/`
   is unambiguous and shared with `contenir/storage`). The `admin-thumb` variant
   is the CMS preview and is never emitted in front-end `srcset`/`sources`.

2. **`storage.asset`** — the on-disk / URL base, in
   `config/autoload/storage.global.php`:

   ```php
   return [
       'storage' => [
           'asset' => [
               'root_path'   => 'public',
               'public_path' => '',
               // 'binary'   => '/opt/homebrew/bin/magick', // optional; auto-discovered otherwise
           ],
       ],
   ];
   ```

## Usage in templates

One key drives everything:

```php
<picture>
    <?= $this->storageSources($asset->path, 'tile') ?>
    <img
        data-lazysrc
        data-lazysrc-srcset="<?= $this->storageSrcSet($asset->path, 'tile') ?>"
        sizes="<?= $this->storageSizes('tile') ?>"
        src="<?= $this->storageUrl($asset->path, 'tile-320') ?>"
        alt="">
</picture>
```