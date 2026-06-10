<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit;

use Contenir\Asset\Laminas\Mvc\ConfigProvider;
use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantController;
use Contenir\Asset\Laminas\Mvc\Module;
use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSources;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSrcSet;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageUrl;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $this->config = (new ConfigProvider())();
    }

    public function testModuleReturnsSameConfig(): void
    {
        self::assertSame($this->config, (new Module())->getConfig());
    }

    public function testStorageDefaultsAreShipped(): void
    {
        $asset = $this->config['storage']['asset'];

        self::assertSame('public', $asset['root_path']);
        self::assertSame(['avif', 'webp'], $asset['variant_formats']);
        self::assertContains(480, $asset['variant_widths']);
    }

    public function testVariantRouteMatchesWidthHeightAndBothTokens(): void
    {
        $regex = '#' . $this->config['router']['routes']['assetvariant']['options']['regex'] . '$#';

        self::assertMatchesRegularExpression($regex, '/asset/library/a/_variant/480x/photo.webp');
        self::assertMatchesRegularExpression($regex, '/asset/library/a/_variant/x900/photo.avif');
        self::assertMatchesRegularExpression($regex, '/asset/library/a/_variant/480x900/photo.jpg');
    }

    public function testVariantRouteCapturesNamedGroups(): void
    {
        $regex = '#' . $this->config['router']['routes']['assetvariant']['options']['regex'] . '$#';

        preg_match($regex, '/asset/library/a/_variant/480x900/photo.webp', $m);

        self::assertSame('library/a', $m['folder']);
        self::assertSame('480x900', $m['dimensions']);
        self::assertSame('photo.webp', $m['filename']);
    }

    public function testControllerAndServicesAreRegistered(): void
    {
        self::assertArrayHasKey(
            AssetVariantController::class,
            $this->config['controllers']['factories'],
        );
        self::assertArrayHasKey(
            AssetUrlBuilder::class,
            $this->config['service_manager']['factories'],
        );
    }

    public function testViewHelpersAreAliasedAndFactoried(): void
    {
        $helpers = $this->config['view_helpers'];

        self::assertSame(StorageUrl::class, $helpers['aliases']['storageUrl']);
        self::assertSame(StorageSrcSet::class, $helpers['aliases']['storageSrcSet']);
        self::assertSame(StorageSources::class, $helpers['aliases']['storageSources']);

        self::assertArrayHasKey(StorageUrl::class, $helpers['factories']);
        self::assertArrayHasKey(StorageSrcSet::class, $helpers['factories']);
        self::assertArrayHasKey(StorageSources::class, $helpers['factories']);
    }
}
