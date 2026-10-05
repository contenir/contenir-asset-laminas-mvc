<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Container\Services;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Contenir\Storage\Image\ImageResizerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use UnexpectedValueException;

final class VariantGeneratorFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws UnexpectedValueException When a dependency has the wrong type.
     */
    public function __invoke(ContainerInterface $container): VariantGenerator
    {
        return new VariantGenerator(
            Services::get($container, ImageResizerInterface::class),
            Services::get($container, ProfileProviderService::class),
            Services::backendOption($container, 'root_path') ?? 'public',
        );
    }
}
