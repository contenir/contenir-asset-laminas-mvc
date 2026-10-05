# View helpers

All helpers return raw strings; escape them for the output context.

| Helper | Returns |
| --- | --- |
| `storageSrcSet(?string $path, string $profile)` | `url 320w, url 640w…` over the profile's ladder, source format |
| `storageSizes(string $profile)` | The profile's `sizes` value, `''` when unknown |
| `storageSources(?string $path, string $profile, bool $lazy = false)` | One `<source type="image/…">` per profile format, with `sizes`; `$lazy` writes `data-lazysrc-srcset` instead of `srcset` |
| `storageUrl(?string $path, ?string $variant = null, ?string $format = null)` | The original URL, or one variant (optionally in another format) |

A null or empty `$path` renders `''`. An unknown profile (srcset/sources) or
variant (url) raises an `E_USER_WARNING` so mistakes surface in development;
`storageUrl()` still returns the URL it would have built.

`AssetUrlBuilder` is the pure URL builder behind them:

```php
$urls = new AssetUrlBuilder('/media');                 // local scheme
$urls->originalUrl('/media/news/a b.jpg');             // "/media/news/a%20b.jpg"
$urls->variantUrl('news/a.jpg', 'card-320', 'webp');   // "/media/news/_variant/card-320/a.webp"

$cdn = new AssetUrlBuilder('https://cdn.test', 's3');  // sibling scheme
$cdn->variantUrl('news/a.jpg', 'card-320', 'avif');    // "https://cdn.test/news/a__card-320.avif"
$cdn->srcset('news/a.jpg', $profile->variants);
```

Path segments are percent-encoded so filenames with spaces keep srcset
parseable. A local path that already starts with the public prefix is not
prefixed twice.
