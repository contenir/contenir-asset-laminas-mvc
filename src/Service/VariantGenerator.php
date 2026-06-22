<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service;

use Contenir\Storage\Image\ImageResizer;
use Contenir\Storage\Variant;
use Throwable;

use function basename;
use function is_file;
use function pathinfo;
use function rtrim;
use function sprintf;
use function strtolower;
use function strtoupper;
use function trim;

use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

/**
 * Resolves a requested keyed variant to a concrete file, generating it on demand
 * via {@see ImageResizer} when missing.
 *
 * The variant definition (width, height, fit, quality) is looked up from
 * {@see ProfileProviderService} by the variant name carried in the URL; the
 * output format comes from the requested filename's extension, falling back to
 * the source format when that format cannot be produced.
 */
final class VariantGenerator
{
    private const VARIANT_DIR = '_variant';

    /** @var list<string> */
    private const SOURCE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    private string $assetRoot;

    public function __construct(
        private ImageResizer $resizer,
        private ProfileProviderService $profiles,
        string $rootPath,
    ) {
        $this->assetRoot = rtrim($rootPath, '/');
    }

    public function generate(string $folder, string $name, string $filename): ?string
    {
        $folder   = trim($folder, '/');
        $filename = basename($filename);

        $variant = $this->profiles->variant($name);
        if ($variant === null) {
            return null;
        }

        $source = $this->resolveSource($folder, $filename);
        if ($source === null) {
            return null;
        }

        $variantDir   = sprintf('%s/asset/%s/%s/%s', $this->assetRoot, $folder, self::VARIANT_DIR, $name);
        $base         = pathinfo($filename, PATHINFO_FILENAME);
        $requestedExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $sourceExt    = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        $dest = $variantDir . '/' . $base . '.' . $requestedExt;
        if (is_file($dest) || $this->materialise($source, $dest, $variant)) {
            return $dest;
        }

        // Requested format unproducible (e.g. no AVIF encoder) — fall back to source format.
        $fallback = $variantDir . '/' . $base . '.' . $sourceExt;
        if (is_file($fallback) || $this->materialise($source, $fallback, $variant)) {
            return $fallback;
        }

        return null;
    }

    private function materialise(string $source, string $dest, Variant $variant): bool
    {
        try {
            $this->resizer->resize($source, $dest, $variant->width, $variant->height, $variant->fit, $variant->quality);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Resolve the source original on disk by basename within <folder>, ignoring
     * the requested variant extension (the URL carries the target format).
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
            foreach ([$ext, strtoupper($ext)] as $candidateExt) {
                $candidate = $dir . '/' . $base . '.' . $candidateExt;
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
