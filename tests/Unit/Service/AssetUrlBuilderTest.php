<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\Service;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class AssetUrlBuilderTest extends TestCase
{
    private function builder(string $base = ''): AssetUrlBuilder
    {
        return new AssetUrlBuilder($base, [320, 480, 960]);
    }

    public function testOriginalUrlStripsLeadingSlashAndPrefixesBase(): void
    {
        $builder = $this->builder('');

        self::assertSame('/asset/library/a/photo.jpg', $builder->originalUrl('/asset/library/a/photo.jpg'));
    }

    public function testOriginalUrlHonoursPublicBase(): void
    {
        $builder = $this->builder('https://cdn.example.com');

        self::assertSame(
            'https://cdn.example.com/asset/library/a/photo.jpg',
            $builder->originalUrl('asset/library/a/photo.jpg'),
        );
    }

    public function testVariantUrlBuildsVariantDirPathForBareWidth(): void
    {
        $builder = $this->builder();

        self::assertSame(
            '/asset/library/a/_variant/480x/photo.jpg',
            $builder->variantUrl('asset/library/a/photo.jpg', 480),
        );
    }

    public function testVariantUrlReplacesExtensionWhenFormatGiven(): void
    {
        $builder = $this->builder();

        self::assertSame(
            '/asset/library/a/_variant/480x/photo.webp',
            $builder->variantUrl('asset/library/a/photo.jpg', '480x', 'webp'),
        );
    }

    #[DataProvider('dimensionProvider')]
    public function testVariantUrlAcceptsFlexibleDimensions(string|int $dimensions, string $expectedToken): void
    {
        $builder = $this->builder();

        self::assertSame(
            "/asset/d/_variant/{$expectedToken}/f.jpg",
            $builder->variantUrl('asset/d/f.jpg', $dimensions),
        );
    }

    /**
     * @return array<string, array{0:string|int,1:string}>
     */
    public static function dimensionProvider(): array
    {
        return [
            'bare int width'    => [480, '480x'],
            'bare string width' => ['480', '480x'],
            'width-bound token' => ['480x', '480x'],
            'height-bound token' => ['x900', 'x900'],
            'both axes token'   => ['480x900', '480x900'],
        ];
    }

    public function testVariantUrlForFileAtRootHasNoLeadingDir(): void
    {
        $builder = $this->builder();

        self::assertSame('/_variant/480x/photo.jpg', $builder->variantUrl('photo.jpg', 480));
    }

    public function testSrcsetUsesConfiguredWidthLadderByDefault(): void
    {
        $builder = $this->builder();

        self::assertSame(
            '/asset/a/_variant/320x/f.jpg 320w, '
            . '/asset/a/_variant/480x/f.jpg 480w, '
            . '/asset/a/_variant/960x/f.jpg 960w',
            $builder->srcset('asset/a/f.jpg'),
        );
    }

    public function testSrcsetAcceptsExplicitWidthsAndFormat(): void
    {
        $builder = $this->builder();

        self::assertSame(
            '/asset/a/_variant/400x/f.avif 400w, /asset/a/_variant/800x/f.avif 800w',
            $builder->srcset('asset/a/f.jpg', [400, 800], 'avif'),
        );
    }

    public function testGetVariantWidthsReturnsIntegers(): void
    {
        $builder = new AssetUrlBuilder('', ['320', '480']);

        self::assertSame([320, 480], $builder->getVariantWidths());
    }
}
