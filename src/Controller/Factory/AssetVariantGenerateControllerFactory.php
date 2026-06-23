<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller\Factory;

use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantGenerateController;
use Contenir\Asset\Laminas\Mvc\Service\OnDemandVariantResolver;
use Psr\Container\ContainerInterface;

final class AssetVariantGenerateControllerFactory
{
    public function __invoke(ContainerInterface $container): AssetVariantGenerateController
    {
        $config = $container->get('config')['storage']['asset'] ?? [];

        return new AssetVariantGenerateController(
            $container->get(OnDemandVariantResolver::class),
            (string) ($config['generate_secret'] ?? ''),
        );
    }
}
