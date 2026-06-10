<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Laminas\View\Helper\AbstractHelper;

/**
 * Emit responsive <source> elements (AVIF, WebP) for a stored asset path, to be
 * placed inside a <picture> before the <img> fallback. The browser picks the
 * first <source> whose type it supports; the <img> (original jpg/png) is the
 * final fallback.
 *
 * Uses lazy attributes (data-lazysrc-srcset) so the framework Lazysrc component —
 * mounted on the sibling <img data-lazysrc> — defers loading. Returns raw HTML;
 * the srcset values are sanitised asset paths.
 */
final class StorageSources extends AbstractHelper
{
    /** @var string[] */
    private array $formats;

    /**
     * @param string[] $formats
     */
    public function __construct(private AssetUrlBuilder $urls, array $formats = ['avif', 'webp'])
    {
        $this->formats = $formats;
    }

    /**
     * @param int[]|null $widths
     */
    public function __invoke(?string $path, ?array $widths = null, ?string $sizes = null): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        $html = '';
        foreach ($this->formats as $format) {
            $html .= sprintf(
                '<source type="image/%s" data-lazysrc-srcset="%s"%s>',
                $format,
                $this->urls->srcset($path, $widths, $format),
                $sizes !== null && $sizes !== '' ? ' data-lazysrc-sizes="' . $sizes . '"' : ''
            );
        }

        return $html;
    }
}
