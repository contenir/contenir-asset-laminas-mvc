<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSrcSet;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function restore_error_handler;
use function set_error_handler;

use const E_USER_WARNING;

#[Group('unit')]
final class StorageSrcSetTest extends TestCase
{
    #[Test]
    public function rendersSrcsetOverProfileVariants(): void
    {
        $expected = '/a/_variant/tile-320/photo.jpg 320w, /a/_variant/tile-640/photo.jpg 640w';

        static::assertSame($expected, $this->helper()('/a/photo.jpg', profile: 'tile'));
    }

    #[Test]
    public function returnsEmptyStringForNullPath(): void
    {
        static::assertSame('', $this->helper()(null, profile: 'tile'));
    }

    #[Test]
    public function warnsAndReturnsEmptyOnUnknownProfile(): void
    {
        $helper = new StorageSrcSet(new ProfileProviderService([]), new AssetUrlBuilder(''));

        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;
            return true;
        }, E_USER_WARNING);

        try {
            $result = $helper('/a/photo.jpg', profile: 'nope');
        } finally {
            restore_error_handler();
        }

        static::assertSame('', $result);
        static::assertCount(1, $warnings);
        static::assertStringContainsString('unknown image profile "nope"', $warnings[0]);
    }

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
}
