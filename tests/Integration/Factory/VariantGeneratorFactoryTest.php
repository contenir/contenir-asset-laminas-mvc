<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Integration\Factory;

use Contenir\Asset\Laminas\Mvc\Service\Factory\VariantGeneratorFactory;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Asset\Laminas\Mvc\Tests\Trait\TemporaryDirectoryTrait;
use Contenir\Storage\Image\ImageResizerInterface;
use Contenir\Storage\Image\StubImageResizer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Builds the generator from config and lets it find originals in a scratch
 * web root.
 */
#[Group('integration')]
final class VariantGeneratorFactoryTest extends TestCase
{
    use TemporaryDirectoryTrait;

    #[Test]
    public function generatesUnderTheConfiguredRootPath(): void
    {
        $this->writePng('asset/foo/pic.png', 10, 10);
        $generator = (new VariantGeneratorFactory())(new InMemoryContainer([
            'config'                      => ['storage' => ['backend' => ['local' => ['root_path' => $this->path()]]]],
            ImageResizerInterface::class  => new StubImageResizer(),
            ProfileProviderService::class => new ProfileProviderService(['thumb' => ['width' => 20]]),
        ]));

        static::assertSame(
            $this->path('asset/foo/_variant/thumb/pic.png'),
            $generator->generate('foo', 'thumb', 'pic.png'),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTemporaryDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
        parent::tearDown();
    }
}
