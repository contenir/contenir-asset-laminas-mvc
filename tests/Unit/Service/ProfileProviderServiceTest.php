<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\Service;

use Contenir\Asset\Laminas\Mvc\Exception\DisallowedVariantException;
use Contenir\Asset\Laminas\Mvc\Profile\Profile;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Storage\Config\PathVariantResolver;
use Contenir\Storage\Variant;
use Contenir\Storage\VariantFit;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class ProfileProviderServiceTest extends TestCase
{
    private function provider(): ProfileProviderService
    {
        return new ProfileProviderService([
            'tile'    => [
                'sizes'    => '(min-width: 768px) 33vw, 100vw',
                'formats'  => ['AVIF', 'WebP'],
                'variants' => [
                    'admin-thumb' => ['width' => 180, 'height' => 180, 'fit' => 'contain'],
                    'tile-320'    => ['width' => 320, 'height' => 240, 'fit' => 'cover', 'quality' => 80],
                    'tile-640'    => ['width' => 640, 'height' => 480, 'fit' => 'cover'],
                ],
            ],
            'gallery' => [
                'variants' => [
                    'gallery-1600' => ['width' => 1600, 'fit' => 'contain'],
                ],
            ],
            'bogus'   => 'not-an-array',
        ], new PathVariantResolver([]));
    }

    public function testHasReportsKnownProfiles(): void
    {
        $provider = $this->provider();

        self::assertTrue($provider->has('tile'));
        self::assertFalse($provider->has('missing'));
    }

    public function testGetReturnsTypedProfileWithLowercasedFormats(): void
    {
        $profile = $this->provider()->get('tile');

        self::assertInstanceOf(Profile::class, $profile);
        self::assertSame('tile', $profile->key);
        self::assertSame('(min-width: 768px) 33vw, 100vw', $profile->sizes);
        self::assertSame(['avif', 'webp'], $profile->formats);
    }

    public function testGetExcludesPreviewVariantFromResponsiveList(): void
    {
        $profile = $this->provider()->get('tile');

        self::assertNotNull($profile);
        $names = array_map(static fn (Variant $variant): string => $variant->name, $profile->variants);
        self::assertSame(['tile-320', 'tile-640'], $names);
    }

    public function testGetReturnsNullForUnknownProfile(): void
    {
        self::assertNull($this->provider()->get('missing'));
    }

    public function testVariantReturnsFlatDefinitionIncludingPreview(): void
    {
        $preview = $this->provider()->variant('admin-thumb');

        self::assertInstanceOf(Variant::class, $preview);
        self::assertSame(180, $preview->width);
    }

    public function testVariantMapsDimensionsFitAndQuality(): void
    {
        $variant = $this->provider()->variant('tile-320');

        self::assertNotNull($variant);
        self::assertSame(320, $variant->width);
        self::assertSame(240, $variant->height);
        self::assertSame(VariantFit::Cover, $variant->fit);
        self::assertSame(80, $variant->quality);
    }

    public function testVariantDefaultsFitToCoverAndNullQuality(): void
    {
        $variant = $this->provider()->variant('tile-640');

        self::assertNotNull($variant);
        self::assertSame(VariantFit::Cover, $variant->fit);
        self::assertNull($variant->quality);
    }

    public function testVariantMapsContainFitAndAutoHeight(): void
    {
        $variant = $this->provider()->variant('gallery-1600');

        self::assertNotNull($variant);
        self::assertSame(VariantFit::Contain, $variant->fit);
        self::assertSame(0, $variant->height);
    }

    public function testVariantReturnsNullForUnknownName(): void
    {
        self::assertNull($this->provider()->variant('nope'));
    }

    public function testNonArrayProfileIsSkipped(): void
    {
        self::assertFalse($this->provider()->has('bogus'));
    }

    public function testCompilesDimensionFamilyToProfileAndExpandedVariants(): void
    {
        $provider = new ProfileProviderService([
            'card' => [
                'fit'        => 'cover',
                'quality'    => 75,
                'formats'    => ['AVIF', 'WebP'],
                'sizes'      => '(min-width: 1024px) 33vw, 100vw',
                'dimensions' => ['320x320', '480x480', '768x768'],
            ],
        ], new PathVariantResolver([]));

        $profile = $provider->get('card');
        self::assertInstanceOf(Profile::class, $profile);
        self::assertSame('(min-width: 1024px) 33vw, 100vw', $profile->sizes);
        self::assertSame(['avif', 'webp'], $profile->formats);
        self::assertSame(
            ['card-320', 'card-480', 'card-768'],
            array_map(static fn (Variant $variant): string => $variant->name, $profile->variants),
        );

        $variant = $provider->variant('card-480');
        self::assertNotNull($variant);
        self::assertSame(480, $variant->width);
        self::assertSame(480, $variant->height);
        self::assertSame(VariantFit::Cover, $variant->fit);
    }

    public function testFlatStandaloneVariantIsRegisteredButNotAProfile(): void
    {
        $provider = new ProfileProviderService([
            'admin-thumb' => ['width' => 180, 'height' => 180, 'fit' => 'contain'],
        ], new PathVariantResolver([]));

        self::assertFalse($provider->has('admin-thumb'));
        $variant = $provider->variant('admin-thumb');
        self::assertNotNull($variant);
        self::assertSame(180, $variant->width);
    }

    public function testPreviewRoleFamilyRegistersVariantsWithoutProfile(): void
    {
        $provider = new ProfileProviderService([
            'thumb' => [
                'role'       => 'preview',
                'fit'        => 'contain',
                'dimensions' => ['180x180'],
            ],
        ], new PathVariantResolver([]));

        self::assertFalse($provider->has('thumb'));
        self::assertNotNull($provider->variant('thumb-180'));
    }

    public function testAssertVariantAllowedEnforcesPathOwnership(): void
    {
        $provider = new ProfileProviderService([], new PathVariantResolver([
            '*'                      => ['admin-thumb'],
            '/asset/library/news/lg' => ['gallery'],
        ]));

        $provider->assertVariantAllowed('/asset/library/news/lg/x.jpg', 'gallery');
        $provider->assertVariantAllowed('/asset/library/news/lg/x.jpg', 'gallery-480');
        $provider->assertVariantAllowed('/anywhere/x.jpg', 'admin-thumb');
        $this->addToAssertionCount(3);

        $this->expectException(DisallowedVariantException::class);
        $provider->assertVariantAllowed('/asset/library/news/lg/x.jpg', 'tile');
    }

    public function testAssertVariantAllowedIsNoOpWhenUnconfigured(): void
    {
        $provider = new ProfileProviderService([], new PathVariantResolver([]));

        $provider->assertVariantAllowed('/anything.jpg', 'whatever');
        $this->addToAssertionCount(1);
    }
}
