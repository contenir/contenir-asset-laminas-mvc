<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Exception\DisallowedVariantException;
use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSrcSet;
use Contenir\Storage\Config\PathVariantResolver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class StorageSrcSetTest extends TestCase
{
    private function helper(): StorageSrcSet
    {
        $profiles = new ProfileProviderService([
            'tile' => [
                'variants' => [
                    'tile-320' => ['width' => 320, 'height' => 240, 'fit' => 'cover'],
                    'tile-640' => ['width' => 640, 'height' => 480, 'fit' => 'cover'],
                ],
            ],
        ], new PathVariantResolver([]));

        return new StorageSrcSet($profiles, new AssetUrlBuilder(''));
    }

    public function testReturnsEmptyStringForNullPath(): void
    {
        self::assertSame('', ($this->helper())(null, 'tile'));
    }

    public function testReturnsEmptyStringForUnknownProfile(): void
    {
        self::assertSame('', ($this->helper())('/a/photo.jpg', 'nope'));
    }

    public function testRendersSrcsetOverProfileVariants(): void
    {
        $expected = '/a/_variant/tile-320/photo.jpg 320w, /a/_variant/tile-640/photo.jpg 640w';

        self::assertSame($expected, ($this->helper())('/a/photo.jpg', 'tile'));
    }

    public function testThrowsWhenPathDoesNotOwnVariant(): void
    {
        $profiles = new ProfileProviderService(
            ['tile' => ['variants' => ['tile-320' => ['width' => 320, 'height' => 240, 'fit' => 'cover']]]],
            new PathVariantResolver(['/asset/library/news/lg' => ['gallery']]),
        );
        $helper = new StorageSrcSet($profiles, new AssetUrlBuilder(''));

        $this->expectException(DisallowedVariantException::class);
        $helper('/asset/library/news/lg/photo.jpg', 'tile');
    }
}
