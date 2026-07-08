<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSrcSet;
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
        ]);

        return new StorageSrcSet($profiles, new AssetUrlBuilder(''));
    }

    public function testReturnsEmptyStringForNullPath(): void
    {
        self::assertSame('', ($this->helper())(null, 'tile'));
    }

    public function testRendersSrcsetOverProfileVariants(): void
    {
        $expected = '/a/_variant/tile-320/photo.jpg 320w, /a/_variant/tile-640/photo.jpg 640w';

        self::assertSame($expected, ($this->helper())('/a/photo.jpg', 'tile'));
    }

    public function testWarnsAndReturnsEmptyOnUnknownProfile(): void
    {
        $helper = new StorageSrcSet(new ProfileProviderService([]), new AssetUrlBuilder(''));

        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;
            return true;
        }, E_USER_WARNING);

        try {
            $result = $helper('/a/photo.jpg', 'nope');
        } finally {
            restore_error_handler();
        }

        self::assertSame('', $result);
        self::assertCount(1, $warnings);
        self::assertStringContainsString('unknown image profile "nope"', $warnings[0]);
    }
}
