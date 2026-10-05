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
 * Render a responsive srcset for a stored asset over a named profile's variant
 * ladder, in the source image format:
 *
 *   data-lazysrc-srcset="<?= $this->storageSrcSet($asset->path, 'tile') ?>"
 *
 * Returns the raw value — escaping is the output context's job.
 *
 * @api
 *
 * @mago-expect analysis:deprecated-class laminas-view deprecates AbstractHelper; moving off it is planned for 3.0.
 */
final class StorageSrcSet extends AbstractHelper
{
    public function __construct(
        private readonly ProfileProviderService $profiles,
        private readonly AssetUrlBuilder $urls,
    ) {}

    public function __invoke(?string $path, string $profile): string
    {
        if (null === $path || '' === $path) {
            return '';
        }

        $definition = $this->profiles->get($profile);
        if (null === $definition) {
            trigger_error(sprintf('StorageSrcSet: unknown image profile "%s".', $profile), E_USER_WARNING);
            return '';
        }

        return $this->urls->srcset($path, $definition->variants);
    }
}
