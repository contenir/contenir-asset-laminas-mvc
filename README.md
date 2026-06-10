# contenir/contenir-asset-laminas-mvc

Laminas MVC adapter for Contenir assets. Serves **on-demand responsive image
variants** — including **WebP** and **AVIF** — backed by
[`contenir/storage`](https://github.com/contenir/storage)'s `ImageResizer`.

This is the MVC flavour of the Contenir asset layer (the `core` + `-laminas-mvc`
convention). The generation engine lives in `contenir/storage`; HTTP serving and
the view helpers live here.

## What it provides

- **`AssetVariantController` + `assetvariant` route** — serves
  `/asset/<folder>/_variant/<dimensions>/<filename>`. Existing variant files are
  served directly by the web server; only missing ones reach the controller, which
  resizes on demand and streams the result.
- **View helpers** for templates:
  - `storageUrl($path, $dimensions = null, $format = null)` — a single URL
    (original, or a `_variant/<dimensions>/` variant).
  - `storageSrcSet($path, $widths = null, $format = null)` — a responsive `srcset`
    over the configured width ladder.
  - `storageSources($path, $widths = null, $sizes = null)` — `<source>` elements
    (AVIF, WebP) for a `<picture>`, with lazy `data-lazysrc-srcset` attributes.
- **`AssetUrlBuilder`** — the pure string URL builder behind the helpers. Returns
  **raw** URLs; escaping is the output context's job (`htmlAttributes()` escapes —
  escaping here too would double-encode).

## URL scheme

```
/asset/<folder>/_variant/<dimensions>/<file>.<fmt>
```

`<dimensions>` is `Wx` (width-bound, e.g. `480x`), `xH` (height-bound, e.g.
`x900`), or `WxH` (e.g. `480x900`) — the same geometry vocabulary as ImageMagick.
`<fmt>` is the source extension, `webp`, or `avif`. The responsive `srcset` ladder
emits `Wx` tokens.

A `WxH` request crops to fill (`Cover`); a single-axis request fits inside
(`Contain`). When the requested format cannot be produced (e.g. the ImageMagick
build lacks AVIF), the controller falls back to a source-format variant so the URL
returns valid image bytes with the real `Content-Type` rather than a 404.

## Installation

```bash
composer require contenir/contenir-asset-laminas-mvc
```

If you use `laminas/laminas-component-installer`, the module is registered
automatically; otherwise add `Contenir\Asset\Laminas\Mvc` to
`config/modules.config.php`.

## Configuration

Override the defaults per-site in `config/autoload/storage.global.php`:

```php
return [
    'storage' => [
        'asset' => [
            'root_path'       => 'public',
            'public_path'     => '',
            'variant_widths'  => [320, 480, 600, 760, 960, 1280, 1440, 1920, 2560],
            'variant_formats' => ['avif', 'webp'],
            // 'binary'       => '/opt/homebrew/bin/magick', // optional; auto-discovered otherwise
        ],
    ],
];
```

## Usage in templates

```php
<picture>
    <?= $this->storageSources($path, [480, 960, 1440]) ?>
    <img
        data-lazysrc
        data-lazysrc-srcset="<?= $this->storageSrcSet($path, [480, 960, 1440]) ?>"
        src="<?= $this->storageUrl($path, '480x') ?>"
        alt="">
</picture>
```
