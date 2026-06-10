<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Laminas\View\Helper\AbstractHelper;

/**
 * Render a responsive srcset for a stored asset path over the configured
 * variant-width ladder, e.g.
 *   /asset/.../_variant/320x/file.jpg 320w, /asset/.../_variant/480x/file.jpg 480w, ...
 *
 * Returns the raw value — escaping is the output context's job (htmlAttributes()
 * escapes; direct echo of these asset paths is safe). Escaping here as well would
 * double-encode when the value is passed through htmlAttributes().
 */
final class StorageSrcSet extends AbstractHelper
{
    public function __construct(private AssetUrlBuilder $urls)
    {
    }

    /**
     * @param int[]|null $widths
     */
    public function __invoke(?string $path, ?array $widths = null, ?string $format = null): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        return $this->urls->srcset($path, $widths, $format);
    }
}
