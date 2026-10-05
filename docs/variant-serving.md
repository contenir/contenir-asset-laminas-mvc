# Serving and generating variants

## Local: the `assetvariant` route

`/asset/<folder>/_variant/<name>/<filename>` is routed to
`AssetVariantController`. The web server serves existing files directly; only
a miss reaches PHP. The controller asks `VariantGenerator` for the file and
returns its bytes with `Content-Type`, `Content-Length` and
`Cache-Control: max-age=31536000, public`, or a 404.

`VariantGenerator::generate($folder, $name, $filename)`:

1. looks the variant up by name (unknown: null);
2. refuses a folder with a `..` segment or a null byte (null);
3. finds the original in `<root>/asset/<folder>/` by basename, trying the
   requested name, then jpg, jpeg, png, gif, webp and avif in either case;
4. writes `<root>/asset/<folder>/_variant/<name>/<base>.<requested ext>`,
   falling back to the source format when the requested one cannot be
   encoded (for example no AVIF delegate), and reuses either if it exists.

## S3/R2: the `assetvariant-generate` endpoint

`GET /asset-variant/generate?key=<sibling key>` with an
`X-Asset-Generate-Secret` header equal to the primary backend's
`generate_secret`. An edge worker calls it on a cache miss.

| Response | When |
| --- | --- |
| 503 `{"error":"generation endpoint not configured"}` | No secret configured |
| 400 | Not an HTTP request, or no `key` (`{"error":"missing key"}`) |
| 403 | Missing or wrong secret |
| 404 | No backend could generate the key |
| 200 `{"url":"…"}` | Generated (or already present) |

`OnDemandVariantResolver` asks each registered backend that implements
`OnDemandVariantGeneratorInterface` in turn; the first non-null URL wins.
