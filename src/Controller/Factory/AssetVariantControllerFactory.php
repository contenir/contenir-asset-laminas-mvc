<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller\Factory;

use Contenir\Asset\Laminas\Mvc\Container\Services;
use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantController;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use UnexpectedValueException;

final class AssetVariantControllerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws UnexpectedValueException When a dependency has the wrong type.
     */
    public function __invoke(ContainerInterface $container): AssetVariantController
    {
        return new AssetVariantController(Services::get($container, VariantGenerator::class));
    }
}
