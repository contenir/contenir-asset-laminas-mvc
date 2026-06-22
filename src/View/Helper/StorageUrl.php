<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Laminas\View\Helper\AbstractHelper;

/**
 * Render a single asset URL — the original, or a specific named variant
 * (optionally in a given format):
 *
 *   <a href="<?= $this->storageUrl($asset->path) ?>">original</a>
 *   <img src="<?= $this->storageUrl($asset->path, 'tile-640') ?>">
 *
 * Returns the raw URL — escaping is the output context's job.
 */
final class StorageUrl extends AbstractHelper
{
    public function __construct(private AssetUrlBuilder $urls)
    {
    }

    public function __invoke(?string $path, ?string $variant = null, ?string $format = null): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        return $variant === null || $variant === ''
            ? $this->urls->originalUrl($path)
            : $this->urls->variantUrl($path, $variant, $format);
    }
}
