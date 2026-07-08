<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageUrl;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class StorageUrlTest extends TestCase
{
    private function helper(): StorageUrl
    {
        return new StorageUrl(
            new ProfileProviderService([
                'tile' => ['variants' => ['tile-640' => ['width' => 640, 'height' => 480, 'fit' => 'cover']]],
            ]),
            new AssetUrlBuilder(''),
        );
    }

    public function testReturnsEmptyStringForNullPath(): void
    {
        self::assertSame('', ($this->helper())(null));
    }

    public function testReturnsOriginalWhenNoVariantGiven(): void
    {
        self::assertSame('/a/photo.jpg', ($this->helper())('/a/photo.jpg'));
    }

    public function testReturnsVariantUrl(): void
    {
        self::assertSame('/a/_variant/tile-640/photo.jpg', ($this->helper())('/a/photo.jpg', 'tile-640'));
    }

    public function testReturnsVariantUrlInRequestedFormat(): void
    {
        self::assertSame('/a/_variant/tile-640/photo.webp', ($this->helper())('/a/photo.jpg', 'tile-640', 'webp'));
    }

    public function testWarnsOnUnknownVariantButStillEmitsUrl(): void
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;
            return true;
        }, E_USER_WARNING);

        try {
            $url = ($this->helper())('/a/photo.jpg', 'nope-999');
        } finally {
            restore_error_handler();
        }

        self::assertSame('/a/_variant/nope-999/photo.jpg', $url, 'URL construction stays deterministic.');
        self::assertCount(1, $warnings);
        self::assertStringContainsString('unknown variant "nope-999"', $warnings[0]);
    }
}
