<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Contenir\Storage\Image\ImageResizer;
use Psr\Container\ContainerInterface;

final class VariantGeneratorFactory
{
    public function __invoke(ContainerInterface $container): VariantGenerator
    {
        $config = $container->get('config')['storage']['asset'] ?? [];

        return new VariantGenerator(
            $container->get(ImageResizer::class),
            $container->get(ProfileProviderService::class),
            (string) ($config['root_path'] ?? 'public'),
        );
    }
}
