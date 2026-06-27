<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller\Factory;

use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantGenerateController;
use Contenir\Asset\Laminas\Mvc\Service\OnDemandVariantResolver;
use Contenir\Storage\Config\StorageConfig;
use Psr\Container\ContainerInterface;

final class AssetVariantGenerateControllerFactory
{
    public function __invoke(ContainerInterface $container): AssetVariantGenerateController
    {
        $backend = StorageConfig::primaryBackendConfig($container->get('config')['storage'] ?? null);

        return new AssetVariantGenerateController(
            $container->get(OnDemandVariantResolver::class),
            (string) ($backend['generate_secret'] ?? ''),
        );
    }
}
