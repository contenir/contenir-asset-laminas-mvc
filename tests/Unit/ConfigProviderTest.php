<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit;

use Contenir\Asset\Laminas\Mvc\ConfigProvider;
use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSrcSet;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    public function testProvidesExpectedTopLevelKeys(): void
    {
        $config = (new ConfigProvider())();

        self::assertArrayHasKey('storage', $config);
        self::assertArrayHasKey('router', $config);
        self::assertArrayHasKey('controllers', $config);
        self::assertArrayHasKey('service_manager', $config);
        self::assertArrayHasKey('view_helpers', $config);
    }

    public function testStorageAssetDefaults(): void
    {
        $asset = (new ConfigProvider())()['storage']['asset'];

        self::assertSame('public', $asset['root_path']);
        self::assertSame('', $asset['public_path']);
    }

    public function testRouteMatchesKeyedVariantPath(): void
    {
        $options = (new ConfigProvider())()['router']['routes']['assetvariant']['options'];

        self::assertStringContainsString('_variant', $options['regex']);
        self::assertStringContainsString('<name>', $options['regex']);
    }

    public function testRegistersKeyedViewHelpers(): void
    {
        $helpers = (new ConfigProvider())()['view_helpers'];

        self::assertArrayHasKey('StorageSrcSet', $helpers['aliases']);
        self::assertArrayHasKey('StorageSizes', $helpers['aliases']);
        self::assertArrayHasKey(StorageSrcSet::class, $helpers['factories']);
    }

    public function testRegistersServices(): void
    {
        $factories = (new ConfigProvider())()['service_manager']['factories'];

        self::assertArrayHasKey(ProfileProviderService::class, $factories);
        self::assertArrayHasKey(AssetUrlBuilder::class, $factories);
        self::assertArrayHasKey(VariantGenerator::class, $factories);
    }
}
