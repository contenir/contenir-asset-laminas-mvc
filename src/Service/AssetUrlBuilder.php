<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service;

use Contenir\Storage\Variant;

use function array_map;
use function basename;
use function dirname;
use function explode;
use function implode;
use function ltrim;
use function preg_replace;
use function rawurlencode;
use function rtrim;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strrpos;
use function substr;

/**
 * Builds public URLs for stored assets and their keyed variants.
 *
 * The URL scheme depends on the storage backend:
 *
 * - local — a variant of `<dir>/file.jpg` lives at `<dir>/_variant/<name>/file.<fmt>`,
 *   served on demand by the local AssetVariantController.
 * - r2/s3 — a variant lives at the sibling key `<dir>/file__<name>.<fmt>` on the
 *   bucket's public CDN base (admin4's sibling-key convention), generated at
 *   upload, by backfill, or on demand at the edge.
 *
 * Returns RAW strings for the local scheme (clean asset paths) and percent-encoded
 * segments for the sibling scheme (CMS filenames may contain spaces that would
 * otherwise break the srcset tokeniser). Escaping remains the output context's job.
 */
final class AssetUrlBuilder
{
    private const VARIANT_DIR = '_variant';

    public const BACKEND_LOCAL = 'local';

    private string $publicBase;
    private bool $siblingScheme;

    public function __construct(string $publicBase, string $backend = self::BACKEND_LOCAL)
    {
        $this->publicBase    = rtrim($publicBase, '/');
        $this->siblingScheme = $backend !== self::BACKEND_LOCAL;
    }

    public function originalUrl(string $path): string
    {
        $key = $this->key($path);

        return $this->siblingScheme ? $this->absoluteEncoded($key) : $this->absolute($key);
    }

    public function variantUrl(string $path, string $name, ?string $format = null): string
    {
        $key = $this->key($path);

        if ($this->siblingScheme) {
            return $this->absoluteEncoded($this->siblingKey($key, $name, $format));
        }

        $dir  = $this->dirname($key);
        $file = basename($key);

        if ($format !== null && $format !== '') {
            $file = preg_replace('/\.[^.\/]+$/', '.' . $format, $file) ?? $file . '.' . $format;
        }

        $variantKey = $dir === ''
            ? sprintf('%s/%s/%s', self::VARIANT_DIR, $name, $file)
            : sprintf('%s/%s/%s/%s', $dir, self::VARIANT_DIR, $name, $file);

        return $this->absolute($variantKey);
    }

    /**
     * @param list<Variant> $variants
     */
    public function srcset(string $path, array $variants, ?string $format = null): string
    {
        $entries = [];
        foreach ($variants as $variant) {
            $entries[] = $this->variantUrl($path, $variant->name, $format) . ' ' . $variant->width . 'w';
        }

        return implode(', ', $entries);
    }

    /**
     * Sibling-object key for the s3/r2 scheme: `<base>__<name>.<format>`, where
     * `<base>` is the key with its extension stripped. A null/empty $format keeps
     * the source extension (the <img> fallback); otherwise the extension is
     * swapped for the requested format (the avif/webp <source>s).
     */
    private function siblingKey(string $key, string $name, ?string $format): string
    {
        $dot  = strrpos($key, '.');
        $base = $dot === false ? $key : substr($key, 0, $dot);
        $ext  = $format !== null && $format !== ''
            ? '.' . $format
            : ($dot === false ? '' : substr($key, $dot));

        return $base . '__' . $name . $ext;
    }

    /**
     * For the local scheme, strip the public-path prefix so the key is relative
     * to the asset root — otherwise the URL doubles up to
     * /asset/library/asset/library/... For the sibling scheme the stored path is
     * the bucket object key and is used verbatim under the CDN base.
     */
    private function key(string $path): string
    {
        $path = ltrim($path, '/');
        if ($this->siblingScheme) {
            return $path;
        }

        $prefix = ltrim($this->publicBase, '/');
        if ($prefix !== '' && str_starts_with($path, $prefix . '/')) {
            $path = substr($path, strlen($prefix) + 1);
        }

        return $path;
    }

    private function dirname(string $path): string
    {
        $dir = dirname($path);

        return ($dir === '.' || $dir === '/' || $dir === '') ? '' : $dir;
    }

    private function absolute(string $key): string
    {
        return $this->publicBase . '/' . ltrim($key, '/');
    }

    private function absoluteEncoded(string $key): string
    {
        $encoded = implode('/', array_map('rawurlencode', explode('/', ltrim($key, '/'))));

        return $this->publicBase . '/' . $encoded;
    }
}
