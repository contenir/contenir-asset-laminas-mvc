<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Storage\Config\StorageConfig;
use Contenir\Storage\Image\ImageResizer;
use Psr\Container\ContainerInterface;

/**
 * Builds the contenir/storage ImageResizer. With no binary path configured it
 * auto-discovers magick/convert from PATH.
 */
final class ImageResizerFactory
{
    public function __invoke(ContainerInterface $container): ImageResizer
    {
        $backend = StorageConfig::primaryBackendConfig($container->get('config')['storage'] ?? null);

        return new ImageResizer($backend['binary'] ?? null);
    }
}
