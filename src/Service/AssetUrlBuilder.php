<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service;

use Contenir\Storage\Variant;

use function basename;
use function dirname;
use function implode;
use function ltrim;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Builds public URLs for stored assets and their keyed variants, matching the
 * contenir/storage LocalFilesystem layout: a variant of `<dir>/file.jpg` lives
 * at `<dir>/_variant/<name>/file.<fmt>`.
 *
 * Returns RAW strings — escaping is the output context's job (htmlAttributes()
 * escapes; direct echo of these clean asset paths is safe).
 */
final class AssetUrlBuilder
{
    private const VARIANT_DIR = '_variant';

    private string $publicBase;

    public function __construct(string $publicBase)
    {
        $this->publicBase = rtrim($publicBase, '/');
    }

    public function originalUrl(string $path): string
    {
        return $this->absolute($this->key($path));
    }

    public function variantUrl(string $path, string $name, ?string $format = null): string
    {
        $key  = $this->key($path);
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
     * Strip the public-path prefix so the key is relative to the asset root —
     * otherwise the URL doubles up to /asset/library/asset/library/...
     */
    private function key(string $path): string
    {
        $path   = ltrim($path, '/');
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
}
