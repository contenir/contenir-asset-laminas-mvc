<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Service\OnDemandVariantResolver;
use Contenir\Storage\StorageManager;
use Psr\Container\ContainerInterface;

/**
 * Wires the resolver to the site-registered {@see StorageManager}. R2 sites must
 * register that service (e.g. via contenir/storage's StorageConfig); the
 * resolver is only constructed when the generation route is hit, so local sites
 * that never expose the route incur no dependency.
 */
final class OnDemandVariantResolverFactory
{
    public function __invoke(ContainerInterface $container): OnDemandVariantResolver
    {
        return new OnDemandVariantResolver($container->get(StorageManager::class));
    }
}
