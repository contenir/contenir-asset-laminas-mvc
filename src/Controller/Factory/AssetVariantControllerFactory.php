<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller\Factory;

use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantController;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Psr\Container\ContainerInterface;

final class AssetVariantControllerFactory
{
    public function __invoke(ContainerInterface $container): AssetVariantController
    {
        return new AssetVariantController($container->get(VariantGenerator::class));
    }
}
