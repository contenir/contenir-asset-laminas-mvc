<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\Service;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Storage\Variant;
use Contenir\Storage\VariantFit;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class AssetUrlBuilderTest extends TestCase
{
    public function testOriginalUrlIsRootRelativeWithEmptyBase(): void
    {
        $builder = new AssetUrlBuilder('');

        self::assertSame('/a/photo.jpg', $builder->originalUrl('/a/photo.jpg'));
    }

    public function testOriginalUrlHonoursPublicBase(): void
    {
        $builder = new AssetUrlBuilder('https://cdn.example.com');

        self::assertSame('https://cdn.example.com/a/photo.jpg', $builder->originalUrl('/a/photo.jpg'));
    }

    public function testVariantUrlInsertsKeyedVariantDir(): void
    {
        $builder = new AssetUrlBuilder('');

        self::assertSame('/a/_variant/tile-320/photo.jpg', $builder->variantUrl('/a/photo.jpg', 'tile-320'));
    }

    public function testVariantUrlSwapsExtensionForFormat(): void
    {
        $builder = new AssetUrlBuilder('');

        self::assertSame('/a/_variant/tile-320/photo.avif', $builder->variantUrl('/a/photo.jpg', 'tile-320', 'avif'));
    }

    public function testVariantUrlForRootLevelFile(): void
    {
        $builder = new AssetUrlBuilder('');

        self::assertSame('/_variant/tile-320/photo.jpg', $builder->variantUrl('photo.jpg', 'tile-320'));
    }

    public function testStripsPublicBasePrefixToAvoidDoubling(): void
    {
        $builder = new AssetUrlBuilder('/asset/library');

        self::assertSame(
            '/asset/library/a/_variant/tile-320/photo.jpg',
            $builder->variantUrl('/asset/library/a/photo.jpg', 'tile-320'),
        );
    }

    public function testSrcsetEmitsWidthDescriptorPerVariant(): void
    {
        $builder  = new AssetUrlBuilder('');
        $variants = [
            new Variant('tile-320', 320, 240, VariantFit::Cover),
            new Variant('tile-640', 640, 480, VariantFit::Cover),
        ];

        self::assertSame(
            '/a/_variant/tile-320/photo.jpg 320w, /a/_variant/tile-640/photo.jpg 640w',
            $builder->srcset('/a/photo.jpg', $variants),
        );
    }

    public function testSrcsetAppliesFormat(): void
    {
        $builder  = new AssetUrlBuilder('');
        $variants = [new Variant('tile-320', 320, 240, VariantFit::Cover)];

        self::assertSame(
            '/a/_variant/tile-320/photo.webp 320w',
            $builder->srcset('/a/photo.jpg', $variants, 'webp'),
        );
    }

    public function testR2BackendBuildsSiblingKeyKeepingSourceExtension(): void
    {
        $builder = new AssetUrlBuilder('https://cdn.example.com', 'r2');

        self::assertSame(
            'https://cdn.example.com/asset/library/photo__card.jpg',
            $builder->variantUrl('asset/library/photo.jpg', 'card'),
        );
    }

    public function testR2BackendSwapsExtensionForFormat(): void
    {
        $builder = new AssetUrlBuilder('https://cdn.example.com', 'r2');

        self::assertSame(
            'https://cdn.example.com/asset/library/photo__card.avif',
            $builder->variantUrl('asset/library/photo.jpg', 'card', 'avif'),
        );
    }

    public function testR2BackendUsesObjectKeyVerbatimWithoutPrefixStripping(): void
    {
        // Unlike local, the CDN host is the public base, so the R2 object key is
        // not a public-path prefix to strip.
        $builder = new AssetUrlBuilder('https://cdn.example.com', 'r2');

        self::assertSame(
            'https://cdn.example.com/photo__card.webp',
            $builder->variantUrl('/photo.jpg', 'card', 'webp'),
        );
    }

    public function testR2BackendSrcsetEmitsSiblingKeysWithWidthDescriptors(): void
    {
        $builder  = new AssetUrlBuilder('https://cdn.example.com', 'r2');
        $variants = [
            new Variant('card-320', 320, 320, VariantFit::Cover),
            new Variant('card-640', 640, 640, VariantFit::Cover),
        ];

        self::assertSame(
            'https://cdn.example.com/a/photo__card-320.avif 320w, '
            . 'https://cdn.example.com/a/photo__card-640.avif 640w',
            $builder->srcset('a/photo.jpg', $variants, 'avif'),
        );
    }

    public function testR2BackendPercentEncodesPathSegments(): void
    {
        $builder = new AssetUrlBuilder('https://cdn.example.com', 'r2');

        self::assertSame(
            'https://cdn.example.com/a/my%20photo__card.jpg',
            $builder->variantUrl('a/my photo.jpg', 'card'),
        );
    }
}
