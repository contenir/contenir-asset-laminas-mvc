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
}
