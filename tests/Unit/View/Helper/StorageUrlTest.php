<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageUrl;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class StorageUrlTest extends TestCase
{
    private function helper(): StorageUrl
    {
        return new StorageUrl(new AssetUrlBuilder('', [480]));
    }

    public function testReturnsEmptyForNullPath(): void
    {
        self::assertSame('', ($this->helper())(null));
    }

    public function testReturnsEmptyForEmptyPath(): void
    {
        self::assertSame('', ($this->helper())(''));
    }

    public function testReturnsOriginalWhenNoDimensions(): void
    {
        self::assertSame('/asset/a/f.jpg', ($this->helper())('asset/a/f.jpg'));
    }

    public function testReturnsVariantWhenDimensionsGiven(): void
    {
        self::assertSame('/asset/a/_variant/480x/f.jpg', ($this->helper())('asset/a/f.jpg', 480));
    }

    public function testReturnsVariantWithFormat(): void
    {
        self::assertSame('/asset/a/_variant/480x/f.webp', ($this->helper())('asset/a/f.jpg', '480x', 'webp'));
    }
}
