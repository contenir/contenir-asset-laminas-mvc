<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Laminas\View\Helper\AbstractHelper;

use function sprintf;

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
 * Returns raw markup — asset paths are clean and the sizes value is config.
 */
final class StorageSources extends AbstractHelper
{
    public function __construct(
        private ProfileProviderService $profiles,
        private AssetUrlBuilder $urls,
    ) {
    }

    public function __invoke(?string $path, string $profile): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        $definition = $this->profiles->get($profile);
        if ($definition === null || $definition->variants === []) {
            return '';
        }

        $output = '';
        foreach ($definition->formats as $format) {
            $srcset = $this->urls->srcset($path, $definition->variants, $format);
            $output .= sprintf(
                '<source type="image/%s" srcset="%s"%s>',
                $format,
                $srcset,
                $definition->sizes === '' ? '' : sprintf(' sizes="%s"', $definition->sizes),
            );
        }

        return $output;
    }
}
