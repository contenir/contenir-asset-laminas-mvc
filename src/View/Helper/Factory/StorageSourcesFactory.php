<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper\Factory;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSources;
use Psr\Container\ContainerInterface;

final class StorageSourcesFactory
{
    public function __invoke(ContainerInterface $container): StorageSources
    {
        $config  = $container->get('config')['storage']['asset'] ?? [];

        return new StorageSources(
            $container->get(AssetUrlBuilder::class),
            $config['variant_formats'] ?? ['avif', 'webp'],
        );
    }
}
