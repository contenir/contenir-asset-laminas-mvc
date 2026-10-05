<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit;

use Contenir\Asset\Laminas\Mvc\ConfigProvider;
use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSrcSet;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function providesExpectedTopLevelKeys(): void
    {
        $config = (new ConfigProvider())();

        static::assertArrayHasKey('storage', $config);
        static::assertArrayHasKey('router', $config);
        static::assertArrayHasKey('controllers', $config);
        static::assertArrayHasKey('service_manager', $config);
        static::assertArrayHasKey('view_helpers', $config);
    }

    #[Test]
    public function registersKeyedViewHelpers(): void
    {
        $helpers = (new ConfigProvider())()['view_helpers'];

        static::assertArrayHasKey('StorageSrcSet', $helpers['aliases']);
        static::assertArrayHasKey('StorageSizes', $helpers['aliases']);
        static::assertArrayHasKey(StorageSrcSet::class, $helpers['factories']);
    }

    #[Test]
    public function registersServices(): void
    {
        $factories = (new ConfigProvider())()['service_manager']['factories'];

        static::assertArrayHasKey(ProfileProviderService::class, $factories);
        static::assertArrayHasKey(AssetUrlBuilder::class, $factories);
        static::assertArrayHasKey(VariantGenerator::class, $factories);
    }

    #[Test]
    public function routeMatchesKeyedVariantPath(): void
    {
        $options = (new ConfigProvider())()['router']['routes']['assetvariant']['options'];

        static::assertStringContainsString('_variant', $options['regex']);
        static::assertStringContainsString('<name>', $options['regex']);
    }

    #[Test]
    public function storageAssetDefaults(): void
    {
        $asset = (new ConfigProvider())()['storage']['asset'];

        static::assertSame('public', $asset['root_path']);
        static::assertSame('', $asset['public_path']);
    }
}
