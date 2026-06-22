<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Psr\Container\ContainerInterface;

final class ProfileProviderServiceFactory
{
    public function __invoke(ContainerInterface $container): ProfileProviderService
    {
        $profiles = $container->get('config')['settings']['storage']['profiles'] ?? [];

        return new ProfileProviderService($profiles);
    }
}
