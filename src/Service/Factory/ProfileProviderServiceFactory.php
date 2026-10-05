<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Container\Services;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use UnexpectedValueException;

final class ProfileProviderServiceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws UnexpectedValueException When a dependency has the wrong type.
     */
    public function __invoke(ContainerInterface $container): ProfileProviderService
    {
        /**
         * Variant definitions are declared once, flat, under storage.variants —
         * the single source the generator also reads.
         */
        return new ProfileProviderService(Services::storageVariants($container));
    }
}
