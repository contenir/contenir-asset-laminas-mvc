<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller\Factory;

use Contenir\Asset\Image\LegacyResizer;
use Contenir\Asset\Laminas\Mvc\Container\Services;
use Contenir\Asset\Laminas\Mvc\Controller\ImageResizeController;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use UnexpectedValueException;

/**
 * @api
 */
final class ImageResizeControllerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws UnexpectedValueException When the resizer has the wrong type.
     *
     * @mago-expect analysis:deprecated-class Builds the deprecated legacy controller for sites still using it.
     */
    public function __invoke(ContainerInterface $container): ImageResizeController
    {
        return new ImageResizeController(Services::get($container, LegacyResizer::class));
    }
}
