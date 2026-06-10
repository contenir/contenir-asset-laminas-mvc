<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service;

use Contenir\Storage\Image\ImageResizer;
use Contenir\Storage\VariantFit;

/**
 * Resolves a requested image variant to a concrete file on disk, generating it on
 * demand via {@see ImageResizer} when missing.
 *
 * Given the URL parts `<folder>`, `<dimensions>` (Wx / xH / WxH) and `<filename>`
 * (whose extension carries the target format), it locates the source original by
 * basename, resizes it into `<folder>/_variant/<dimensions>/<base>.<fmt>` and
 * returns the produced path.
 *
 * Format fallback: when the requested format cannot be produced (e.g. the
 * ImageMagick build lacks AVIF), a source-format variant is generated and returned
 * instead, so callers can always serve valid image bytes rather than a 404.
 */
final class VariantGenerator
{
    private const VARIANT_DIR = '_variant';

    /** @var string[] Extensions considered valid resize sources. */
    private const SOURCE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    private string $assetRoot;

    public function __construct(private ImageResizer $resizer, string $rootPath)
    {
        $this->assetRoot = rtrim($rootPath, '/');
    }

    /**
     * @return string|null Absolute-ish path to the variant file, or null when the
     *                     source cannot be found / no variant can be produced.
     */
    public function generate(string $folder, string $dimensions, string $filename): ?string
    {
        $folder   = trim($folder, '/');
        $filename = basename($filename);

        [$width, $height] = $this->parseDimensions($dimensions);
        if ($width <= 0 && $height <= 0) {
            return null;
        }

        $source = $this->resolveSource($folder, $filename);
        if ($source === null) {
            return null;
        }

        $variantDir   = sprintf('%s/asset/%s/%s/%s', $this->assetRoot, $folder, self::VARIANT_DIR, $dimensions);
        $base         = pathinfo($filename, PATHINFO_FILENAME);
        $requestedExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $sourceExt    = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $fit          = ($width > 0 && $height > 0) ? VariantFit::Cover : VariantFit::Contain;

        $dest = $variantDir . '/' . $base . '.' . $requestedExt;
        if (is_file($dest)) {
            return $dest;
        }

        try {
            $this->resizer->resize($source, $dest, $width, $height, $fit);

            return $dest;
        } catch (\Throwable) {
            // Requested format unproducible — fall back to the source format.
        }

        $fallback = $variantDir . '/' . $base . '.' . $sourceExt;
        if (is_file($fallback)) {
            return $fallback;
        }

        try {
            $this->resizer->resize($source, $fallback, $width, $height, $fit);

            return $fallback;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{0:int,1:int} [width, height]; a zero means "auto" for that axis.
     */
    private function parseDimensions(string $dimensions): array
    {
        if (! str_contains($dimensions, 'x')) {
            return [0, 0];
        }

        [$w, $h] = explode('x', $dimensions, 2);

        return [(int) $w, (int) $h];
    }

    /**
     * Resolve the source original on disk by basename within <folder>, regardless
     * of the requested variant's extension (the URL carries the target format, not
     * the source format).
     */
    private function resolveSource(string $folder, string $filename): ?string
    {
        $dir  = sprintf('%s/asset/%s', $this->assetRoot, $folder);
        $base = pathinfo($filename, PATHINFO_FILENAME);

        $exact = $dir . '/' . $filename;
        if (is_file($exact)) {
            return $exact;
        }

        foreach (self::SOURCE_EXTENSIONS as $ext) {
            $candidate = $dir . '/' . $base . '.' . $ext;
            if (is_file($candidate)) {
                return $candidate;
            }
            $candidate = $dir . '/' . $base . '.' . strtoupper($ext);
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
