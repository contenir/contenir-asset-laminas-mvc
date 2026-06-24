<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service\Factory;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Psr\Container\ContainerInterface;

use function is_array;

final class ProfileProviderServiceFactory
{
    public function __invoke(ContainerInterface $container): ProfileProviderService
    {
        $config   = $container->get('config');
        $profiles = [];

        // Art-directed families declared once under each storage backend's
        // `variants` (the unified single source the generator also reads).
        foreach ((array) ($config['storage']['profiles'] ?? []) as $backend) {
            if (! is_array($backend) || ! is_array($backend['variants'] ?? null)) {
                continue;
            }
            foreach ($backend['variants'] as $name => $declaration) {
                $profiles[(string) $name] = $declaration;
            }
        }

        // Legacy explicit front-end profiles, kept until a site migrates; they
        // override so a half-migrated site keeps its hand-written profile.
        foreach ((array) ($config['settings']['storage']['profiles'] ?? []) as $name => $declaration) {
            $profiles[(string) $name] = $declaration;
        }

        return new ProfileProviderService($profiles);
    }
}
