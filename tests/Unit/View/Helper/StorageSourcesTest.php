<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSources;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class StorageSourcesTest extends TestCase
{
    /**
     * @param list<string> $formats
     */
    private function helper(array $formats = ['avif', 'webp']): StorageSources
    {
        $profiles = new ProfileProviderService([
            'tile' => [
                'sizes'    => '100vw',
                'formats'  => $formats,
                'variants' => [
                    'tile-320' => ['width' => 320, 'height' => 240, 'fit' => 'cover'],
                ],
            ],
        ]);

        return new StorageSources($profiles, new AssetUrlBuilder(''));
    }

    public function testReturnsEmptyStringForNullPath(): void
    {
        self::assertSame('', ($this->helper())(null, 'tile'));
    }

    public function testReturnsEmptyStringWhenProfileDeclaresNoFormats(): void
    {
        self::assertSame('', ($this->helper([]))('/a/photo.jpg', 'tile'));
    }

    public function testRendersOneSourceTagPerFormatWithSizes(): void
    {
        $html = ($this->helper())('/a/photo.jpg', 'tile');

        self::assertStringContainsString(
            '<source type="image/avif" srcset="/a/_variant/tile-320/photo.avif 320w" sizes="100vw">',
            $html,
        );
        self::assertStringContainsString(
            '<source type="image/webp" srcset="/a/_variant/tile-320/photo.webp 320w" sizes="100vw">',
            $html,
        );
    }

    public function testLazyModeEmitsDataLazysrcSrcset(): void
    {
        $html = ($this->helper())('/a/photo.jpg', 'tile', true);

        self::assertStringContainsString(
            '<source type="image/avif" data-lazysrc-srcset="/a/_variant/tile-320/photo.avif 320w" sizes="100vw">',
            $html,
        );
        self::assertStringNotContainsString(' srcset="', $html, 'Lazy mode must not emit a live srcset attribute.');
    }
}
