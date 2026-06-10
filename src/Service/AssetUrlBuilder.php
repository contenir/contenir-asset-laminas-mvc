<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service;

/**
 * Pure string URL builder for on-demand image variants.
 *
 * A variant of `/dir/file.jpg` lives at `/dir/_variant/<dimensions>/file.<fmt>`,
 * where `<dimensions>` is `Wx` / `xH` / `WxH` (e.g. `480x`, `x900`, `480x900`) and
 * `<fmt>` is the source extension, `webp`, or `avif`. Missing variants are
 * generated lazily by AssetVariantController on first request.
 *
 * Returns RAW URLs — escaping is the output context's job (htmlAttributes()
 * escapes; direct echo of these sanitised asset paths is safe). Escaping here too
 * would double-encode when the value passes through htmlAttributes().
 */
final class AssetUrlBuilder
{
    private const VARIANT_DIR = '_variant';

    private string $publicBase;

    /** @var int[] */
    private array $variantWidths;

    /**
     * @param int[] $variantWidths
     */
    public function __construct(string $publicBase, array $variantWidths)
    {
        $this->publicBase    = rtrim($publicBase, '/');
        $this->variantWidths = array_map('intval', $variantWidths);
    }

    public function originalUrl(string $path): string
    {
        return $this->absolute(ltrim($path, '/'));
    }

    /**
     * @param string|int $dimensions A `Wx`/`xH`/`WxH` token, or a bare width (int|"480").
     */
    public function variantUrl(string $path, string|int $dimensions, ?string $format = null): string
    {
        $dimensions = $this->normaliseDimensions($dimensions);
        $path       = ltrim($path, '/');
        $dir        = $this->dirname($path);
        $file       = basename($path);

        if ($format !== null && $format !== '') {
            $file = preg_replace('/\.[^.\/]+$/', '.' . $format, $file) ?? $file . '.' . $format;
        }

        $key = $dir === ''
            ? sprintf('%s/%s/%s', self::VARIANT_DIR, $dimensions, $file)
            : sprintf('%s/%s/%s/%s', $dir, self::VARIANT_DIR, $dimensions, $file);

        return $this->absolute($key);
    }

    /**
     * Responsive srcset over the width ladder. Each entry is a width-bound (`Wx`)
     * variant with a `<width>w` descriptor.
     *
     * @param int[]|null $widths
     */
    public function srcset(string $path, ?array $widths = null, ?string $format = null): string
    {
        $widths ??= $this->variantWidths;

        $entries = [];
        foreach ($widths as $width) {
            $width     = (int) $width;
            $entries[] = $this->variantUrl($path, $width . 'x', $format) . ' ' . $width . 'w';
        }

        return implode(', ', $entries);
    }

    /** @return int[] */
    public function getVariantWidths(): array
    {
        return $this->variantWidths;
    }

    /**
     * A bare width ("480" or 480) becomes the width-bound token "480x".
     */
    private function normaliseDimensions(string|int $dimensions): string
    {
        $dimensions = (string) $dimensions;

        return str_contains($dimensions, 'x') ? $dimensions : $dimensions . 'x';
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
