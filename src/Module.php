<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc;

/**
 * Laminas MVC module entry point. Returns the {@see ConfigProvider} wiring
 * (route, controller, view helpers, services, storage defaults).
 */
final class Module
{
    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return (new ConfigProvider())();
    }
}
