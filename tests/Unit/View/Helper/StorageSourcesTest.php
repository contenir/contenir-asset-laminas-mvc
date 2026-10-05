<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSources;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function restore_error_handler;
use function set_error_handler;

use const E_USER_WARNING;

#[Group('unit')]
final class StorageSourcesTest extends TestCase
{
    #[Test]
    public function lazyModeEmitsDataLazysrcSrcset(): void
    {
        $html = $this->helper()('/a/photo.jpg', profile: 'tile', lazy: true);

        static::assertStringContainsString(
            '<source type="image/avif" data-lazysrc-srcset="/a/_variant/tile-320/photo.avif 320w" sizes="100vw">',
            $html,
        );
        static::assertStringNotContainsString(' srcset="', $html, 'Lazy mode must not emit a live srcset attribute.');
    }

    #[Test]
    public function omitsTheSizesAttributeWhenTheProfileHasNone(): void
    {
        $helper = new StorageSources(
            new ProfileProviderService(['tile' => ['variants' => ['tile-1' => ['width' => 1]], 'formats' => ['webp']]]),
            new AssetUrlBuilder(''),
        );

        static::assertSame('<source type="image/webp" srcset="/_variant/tile-1/a.webp 1w">', $helper(
            'a.jpg',
            profile: 'tile',
        ));
    }

    #[Test]
    public function rendersOneSourceTagPerFormatWithSizes(): void
    {
        $html = $this->helper()('/a/photo.jpg', profile: 'tile');

        static::assertStringContainsString(
            '<source type="image/avif" srcset="/a/_variant/tile-320/photo.avif 320w" sizes="100vw">',
            $html,
        );
        static::assertStringContainsString(
            '<source type="image/webp" srcset="/a/_variant/tile-320/photo.webp 320w" sizes="100vw">',
            $html,
        );
    }

    #[Test]
    public function returnsEmptyStringForNullPath(): void
    {
        static::assertSame('', $this->helper()(null, profile: 'tile'));
    }

    #[Test]
    public function returnsEmptyStringWhenProfileDeclaresNoFormats(): void
    {
        static::assertSame('', $this->helper([])('/a/photo.jpg', profile: 'tile'));
    }

    #[Test]
    public function returnsEmptyStringWhenTheProfileHasNoVariants(): void
    {
        $helper = new StorageSources(new ProfileProviderService([
            'empty' => ['variants' => [], 'formats' => ['webp']],
        ]), new AssetUrlBuilder(''));

        static::assertSame('', $helper('a.jpg', profile: 'empty'));
    }

    #[Test]
    public function warnsAndReturnsEmptyOnUnknownProfile(): void
    {
        $helper = new StorageSources(new ProfileProviderService([]), new AssetUrlBuilder(''));

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
}
