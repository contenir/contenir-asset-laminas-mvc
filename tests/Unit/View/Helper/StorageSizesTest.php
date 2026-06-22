<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSizes;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class StorageSizesTest extends TestCase
{
    private function helper(): StorageSizes
    {
        return new StorageSizes(new ProfileProviderService([
            'tile' => ['sizes' => '(min-width: 768px) 33vw, 100vw', 'variants' => []],
        ]));
    }

    public function testReturnsConfiguredSizes(): void
    {
        self::assertSame('(min-width: 768px) 33vw, 100vw', ($this->helper())('tile'));
    }

    public function testReturnsEmptyStringForUnknownProfile(): void
    {
        self::assertSame('', ($this->helper())('nope'));
    }
}
