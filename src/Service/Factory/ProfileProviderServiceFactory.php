<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Psr\Container\ContainerInterface;

use function is_array;

final class ProfileProviderServiceFactory
{
    public function __invoke(ContainerInterface $container): ProfileProviderService
    {
        $storage = (array) ($container->get('config')['storage'] ?? []);

        // Variant definitions are declared once, flat, under storage.variants —
        // the single source the generator also reads.
        $variants = is_array($storage['variants'] ?? null) ? $storage['variants'] : [];

        return new ProfileProviderService($variants);
    }
}
