<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageUrl;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function restore_error_handler;
use function set_error_handler;

use const E_USER_WARNING;

#[Group('unit')]
final class StorageUrlTest extends TestCase
{
    #[Test]
    public function returnsEmptyStringForNullPath(): void
    {
        static::assertSame('', $this->helper()(null));
    }

    #[Test]
    public function returnsOriginalWhenNoVariantGiven(): void
    {
        static::assertSame('/a/photo.jpg', $this->helper()('/a/photo.jpg'));
    }

    #[Test]
    public function returnsVariantUrl(): void
    {
        static::assertSame('/a/_variant/tile-640/photo.jpg', $this->helper()('/a/photo.jpg', variant: 'tile-640'));
    }

    #[Test]
    public function returnsVariantUrlInRequestedFormat(): void
    {
        static::assertSame('/a/_variant/tile-640/photo.webp', $this->helper()(
            '/a/photo.jpg',
            variant: 'tile-640',
            format: 'webp',
        ));
    }

    #[Test]
    public function warnsOnUnknownVariantButStillEmitsUrl(): void
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;
            return true;
        }, E_USER_WARNING);

        try {
            $url = $this->helper()('/a/photo.jpg', variant: 'nope-999');
        } finally {
            restore_error_handler();
        }

        static::assertSame('/a/_variant/nope-999/photo.jpg', $url, 'URL construction stays deterministic.');
        static::assertCount(1, $warnings);
        static::assertStringContainsString('unknown variant "nope-999"', $warnings[0]);
    }

    private function helper(): StorageUrl
    {
        return new StorageUrl(
            new ProfileProviderService([
                'tile' => ['variants' => ['tile-640' => ['width' => 640, 'height' => 480, 'fit' => 'cover']]],
            ]),
            new AssetUrlBuilder(''),
        );
    }
}
