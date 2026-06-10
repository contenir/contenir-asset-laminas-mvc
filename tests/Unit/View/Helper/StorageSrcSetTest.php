<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSrcSet;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class StorageSrcSetTest extends TestCase
{
    private function helper(): StorageSrcSet
    {
        return new StorageSrcSet(new AssetUrlBuilder('', [320, 480]));
    }

    public function testReturnsEmptyForNullPath(): void
    {
        self::assertSame('', ($this->helper())(null));
    }

    public function testReturnsEmptyForEmptyPath(): void
    {
        self::assertSame('', ($this->helper())(''));
    }

    public function testRendersLadderSrcset(): void
    {
        self::assertSame(
            '/asset/a/_variant/320x/f.jpg 320w, /asset/a/_variant/480x/f.jpg 480w',
            ($this->helper())('asset/a/f.jpg'),
        );
    }

    public function testRendersExplicitWidthsAndFormat(): void
    {
        self::assertSame(
            '/asset/a/_variant/600x/f.webp 600w',
            ($this->helper())('asset/a/f.jpg', [600], 'webp'),
        );
    }
}
