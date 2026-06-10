<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSources;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class StorageSourcesTest extends TestCase
{
    private function helper(array $formats = ['avif', 'webp']): StorageSources
    {
        return new StorageSources(new AssetUrlBuilder('', [320, 480]), $formats);
    }

    public function testReturnsEmptyForNullPath(): void
    {
        self::assertSame('', ($this->helper())(null));
    }

    public function testReturnsEmptyForEmptyPath(): void
    {
        self::assertSame('', ($this->helper())(''));
    }

    public function testEmitsOneSourcePerFormatInOrder(): void
    {
        $html = ($this->helper())('asset/a/f.jpg');

        self::assertSame(
            '<source type="image/avif" data-lazysrc-srcset="'
            . '/asset/a/_variant/320x/f.avif 320w, /asset/a/_variant/480x/f.avif 480w">'
            . '<source type="image/webp" data-lazysrc-srcset="'
            . '/asset/a/_variant/320x/f.webp 320w, /asset/a/_variant/480x/f.webp 480w">',
            $html,
        );
    }

    public function testIncludesSizesAttributeWhenProvided(): void
    {
        $html = ($this->helper(['webp']))('asset/a/f.jpg', [320], '(min-width: 40em) 50vw, 100vw');

        self::assertStringContainsString(
            'data-lazysrc-sizes="(min-width: 40em) 50vw, 100vw"',
            $html,
        );
    }

    public function testHonoursExplicitWidths(): void
    {
        $html = ($this->helper(['webp']))('asset/a/f.jpg', [600]);

        self::assertSame(
            '<source type="image/webp" data-lazysrc-srcset="/asset/a/_variant/600x/f.webp 600w">',
            $html,
        );
    }
}
