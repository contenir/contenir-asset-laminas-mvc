<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Psr\Container\ContainerInterface;

final class AssetUrlBuilderFactory
{
    public function __invoke(ContainerInterface $container): AssetUrlBuilder
    {
        $config = $container->get('config')['storage']['asset'] ?? [];

        return new AssetUrlBuilder(
            (string) ($config['public_path'] ?? ''),
            $config['variant_widths'] ?? [320, 480, 600, 760, 960, 1280, 1440, 1920, 2560],
        );
    }
}
