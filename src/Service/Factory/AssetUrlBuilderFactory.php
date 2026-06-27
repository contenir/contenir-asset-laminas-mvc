<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Storage\Config\StorageConfig;
use Psr\Container\ContainerInterface;

final class AssetUrlBuilderFactory
{
    public function __invoke(ContainerInterface $container): AssetUrlBuilder
    {
        $backend = StorageConfig::primaryBackendConfig($container->get('config')['storage'] ?? null);
        $type    = (string) ($backend['type'] ?? AssetUrlBuilder::BACKEND_LOCAL);

        // Local serves variants under the web root (public_path); object stores
        // serve sibling objects from the bucket's public CDN base (public_base_url).
        $publicBase = $type === AssetUrlBuilder::BACKEND_LOCAL
            ? (string) ($backend['public_path'] ?? '')
            : (string) ($backend['public_base_url'] ?? '');

        return new AssetUrlBuilder($publicBase, $type);
    }
}
