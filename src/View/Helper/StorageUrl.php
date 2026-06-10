<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Laminas\View\Helper\AbstractHelper;

/**
 * Render a single asset URL — the original when no dimensions are given, otherwise
 * the matching `/_variant/<dimensions>/` variant URL.
 *
 * Returns the raw URL — escaping is the output context's job (htmlAttributes()
 * escapes; direct echo of these asset paths is safe). Escaping here as well would
 * double-encode when the value is passed through htmlAttributes().
 */
final class StorageUrl extends AbstractHelper
{
    public function __construct(private AssetUrlBuilder $urls)
    {
    }

    public function __invoke(?string $path, string|int|null $dimensions = null, ?string $format = null): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        return $dimensions === null
            ? $this->urls->originalUrl($path)
            : $this->urls->variantUrl($path, $dimensions, $format);
    }
}
