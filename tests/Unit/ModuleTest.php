<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit;

use Contenir\Asset\Laminas\Mvc\ConfigProvider;
use Contenir\Asset\Laminas\Mvc\Module;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ModuleTest extends TestCase
{
    #[Test]
    public function moduleConfigIsTheConfigProviderWiring(): void
    {
        static::assertSame((new ConfigProvider())(), (new Module())->getConfig());
    }
}
