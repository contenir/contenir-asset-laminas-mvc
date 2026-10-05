<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Laminas\View\Helper\AbstractHelper;

use function sprintf;
use function trigger_error;

use const E_USER_WARNING;

/**
 * Render `<source>` elements for a `<picture>`, one per extra output format
 * declared on the profile (e.g. AVIF then WebP), each carrying the profile's
 * `sizes` attribute:
 *
 *   <picture>
 *     <?= $this->storageSources($asset->path, 'tile') ?>
 *     <img ... srcset="<?= $this->storageSrcSet($asset->path, 'tile') ?>"
 *          sizes="<?= $this->storageSizes('tile') ?>">
 *   </picture>
 *
 * Pass $lazy = true to emit `data-lazysrc-srcset` instead of `srcset`, so a
 * data-attribute lazy-loader can defer the avif/webp sources the same way it
 * defers the <img>; without it a live <source srcset> would fetch eagerly and
 * defeat lazy loading.
 *
 * Returns raw markup — asset paths are clean and the sizes value is config.
 *
 * @api
 *
 * @mago-expect analysis:deprecated-class laminas-view deprecates AbstractHelper; moving off it is planned for 3.0.
 */
final class StorageSources extends AbstractHelper
{
    public function __construct(
        private readonly ProfileProviderService $profiles,
        private readonly AssetUrlBuilder $urls,
    ) {}

    /**
     * @mago-expect lint:no-boolean-flag-parameter Published template API: `$lazy` switches the srcset attribute name.
     */
    public function __invoke(?string $path, string $profile, bool $lazy = false): string
    {
        if (null === $path || '' === $path) {
            return '';
        }

        $definition = $this->profiles->get($profile);
        if (null === $definition) {
            trigger_error(sprintf('StorageSources: unknown image profile "%s".', $profile), E_USER_WARNING);
            return '';
        }
        if ([] === $definition->variants) {
            return '';
        }

        $srcsetAttr = $lazy ? 'data-lazysrc-srcset' : 'srcset';

        $output = '';
        foreach ($definition->formats as $format) {
            $srcset = $this->urls->srcset($path, $definition->variants, $format);
            $output .= sprintf(
                '<source type="image/%s" %s="%s"%s>',
                $format,
                $srcsetAttr,
                $srcset,
                '' === $definition->sizes ? '' : sprintf(' sizes="%s"', $definition->sizes),
            );
        }

        return $output;
    }
}
