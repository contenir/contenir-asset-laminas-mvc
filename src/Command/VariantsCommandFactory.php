<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Command;

use Contenir\Storage\StorageManager;
use Psr\Container\ContainerInterface;

final class VariantsCommandFactory
{
    public function __invoke(ContainerInterface $container): VariantsCommand
    {
        // What each path is entitled to is resolved inside the backend, from
        // storage.variants and storage.paths — the command needs no config.
        return new VariantsCommand($container->get(StorageManager::class));
    }
}
