<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper\Factory;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSrcSet;
use Psr\Container\ContainerInterface;

final class StorageSrcSetFactory
{
    public function __invoke(ContainerInterface $container): StorageSrcSet
    {
        return new StorageSrcSet(
            $container->get(ProfileProviderService::class),
            $container->get(AssetUrlBuilder::class),
        );
    }
}
