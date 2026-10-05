<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Laminas\View\Helper\AbstractHelper;

/**
 * Render the configured `sizes` attribute value for a named profile:
 *
 *   sizes="<?= $this->storageSizes('tile') ?>"
 *
 * @api
 *
 * @mago-expect analysis:deprecated-class laminas-view deprecates AbstractHelper; moving off it is planned for 3.0.
 */
final class StorageSizes extends AbstractHelper
{
    public function __construct(
        private readonly ProfileProviderService $profiles,
    ) {}

    public function __invoke(string $profile): string
    {
        $definition = $this->profiles->get($profile);

        return null === $definition ? '' : $definition->sizes;
    }
}
