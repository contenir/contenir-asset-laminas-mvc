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
        return new StorageUrl(new AssetUrlBuilder(''));
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
}
