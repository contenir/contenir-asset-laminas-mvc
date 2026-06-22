<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\View\Helper\Factory;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSizes;
use Psr\Container\ContainerInterface;

final class StorageSizesFactory
{
    public function __invoke(ContainerInterface $container): StorageSizes
    {
        return new StorageSizes($container->get(ProfileProviderService::class));
    }
}
