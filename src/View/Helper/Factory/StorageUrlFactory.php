<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper\Factory;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageUrl;
use Psr\Container\ContainerInterface;

final class StorageUrlFactory
{
    public function __invoke(ContainerInterface $container): StorageUrl
    {
        return new StorageUrl($container->get(AssetUrlBuilder::class));
    }
}
