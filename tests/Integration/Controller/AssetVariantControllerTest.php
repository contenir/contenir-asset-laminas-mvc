<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Integration\Controller;

use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantController;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Contenir\Asset\Laminas\Mvc\Tests\Trait\TemporaryDirectoryTrait;
use Contenir\Storage\Image\StubImageResizer;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Serves real files generated into a scratch web root.
 */
#[Group('integration')]
final class AssetVariantControllerTest extends TestCase
{
    use TemporaryDirectoryTrait;

    #[Test]
    public function answersNotFoundWhenTheVariantCannotBeProduced(): void
    {
        static::assertSame(
            404,
            $this->dispatch(['folder' => 'foo', 'name' => 'thumb', 'filename' => 'none.png'])->getStatusCode(),
        );
    }

    #[Test]
    public function servesTheGeneratedVariantWithLongLivedCaching(): void
    {
        $this->writePng('asset/My Folder/pic one.png', 10, 10);

        $response = $this->dispatch(['folder' => 'My%20Folder', 'name' => 'thumb', 'filename' => 'pic%20one.png']);

        static::assertSame(200, $response->getStatusCode());
        static::assertSame('STUB:20x20:Cover', $response->getContent());
        static::assertSame('text/plain', $response->getHeaders()->get('Content-Type')?->getFieldValue());
        static::assertSame('16', $response->getHeaders()->get('Content-Length')?->getFieldValue());
        static::assertSame(
            'max-age=31536000, public',
            $response->getHeaders()->get('Cache-Control')?->getFieldValue(),
        );
    }

    #[Test]
    public function treatsMissingRouteParametersAsEmpty(): void
    {
        static::assertSame(404, $this->dispatch([])->getStatusCode());
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

    /**
     * @param array<string, string> $params
     */
    private function dispatch(array $params): Response
    {
        $profiles   = new ProfileProviderService(['thumb' => ['width' => 20, 'height' => 20]]);
        $controller = new AssetVariantController(
            new VariantGenerator(new StubImageResizer(), $profiles, $this->path()),
        );
        $event = new MvcEvent();
        $event->setRouteMatch(new RouteMatch(['action' => 'index', ...$params]));
        $controller->setEvent($event);

        $result = $controller->dispatch(new Request());
        static::assertInstanceOf(Response::class, $result);

        return $result;
    }
}
