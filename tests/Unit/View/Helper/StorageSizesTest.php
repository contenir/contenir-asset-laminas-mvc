<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\View\Helper;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSizes;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class StorageSizesTest extends TestCase
{
    #[Test]
    public function returnsConfiguredSizes(): void
    {
        static::assertSame('(min-width: 768px) 33vw, 100vw', $this->helper()('tile'));
    }

    #[Test]
    public function returnsEmptyStringForUnknownProfile(): void
    {
        static::assertSame('', $this->helper()('nope'));
    }

    private function helper(): StorageSizes
    {
        return new StorageSizes(new ProfileProviderService([
            'tile' => ['sizes' => '(min-width: 768px) 33vw, 100vw', 'variants' => []],
        ]));
    }
}
