<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Command;

use Contenir\Storage\StorageManager;
use Psr\Container\ContainerInterface;

final class VariantsCommandFactory
{
    public function __invoke(ContainerInterface $container): VariantsCommand
    {
        /** @var array<string, mixed> $config */
        $config = $container->get('config');

        return new VariantsCommand($container->get(StorageManager::class), $config);
    }
}
