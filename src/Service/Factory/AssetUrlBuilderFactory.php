<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Psr\Container\ContainerInterface;

final class AssetUrlBuilderFactory
{
    public function __invoke(ContainerInterface $container): AssetUrlBuilder
    {
        $config  = $container->get('config')['storage']['asset'] ?? [];
        $backend = (string) ($config['backend'] ?? AssetUrlBuilder::BACKEND_LOCAL);

        // Local serves variants under the web root (public_path); r2/s3 serve
        // sibling objects from the bucket's public CDN base (public_base_url).
        $publicBase = $backend === AssetUrlBuilder::BACKEND_LOCAL
            ? (string) ($config['public_path'] ?? '')
            : (string) ($config['public_base_url'] ?? '');

        return new AssetUrlBuilder($publicBase, $backend);
    }
}
