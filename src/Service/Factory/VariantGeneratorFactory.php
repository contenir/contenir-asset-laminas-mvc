<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Contenir\Storage\Config\StorageConfig;
use Contenir\Storage\Image\ImageResizer;
use Psr\Container\ContainerInterface;

final class VariantGeneratorFactory
{
    public function __invoke(ContainerInterface $container): VariantGenerator
    {
        $backend = StorageConfig::primaryBackendConfig($container->get('config')['storage'] ?? null);

        return new VariantGenerator(
            $container->get(ImageResizer::class),
            $container->get(ProfileProviderService::class),
            (string) ($backend['root_path'] ?? 'public'),
        );
    }
}
