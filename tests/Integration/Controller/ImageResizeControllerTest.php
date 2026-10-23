<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Integration\Controller;

use Contenir\Asset\Image\LegacyResizer;
use Contenir\Asset\Laminas\Mvc\Controller\Factory\ImageResizeControllerFactory;
use Contenir\Asset\Laminas\Mvc\Controller\ImageResizeController;
use Contenir\Asset\Laminas\Mvc\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Asset\Laminas\Mvc\Tests\TestAsset\Image\CopyingImageConverter;
use Contenir\Asset\Laminas\Mvc\Tests\Trait\TemporaryDirectoryTrait;
use Laminas\Http\Response;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function is_file;
use function str_contains;

/**
 * Serves real files resized into a scratch web root through contenir-asset's
 * legacy resizer.
 *
 * @mago-expect analysis:deprecated-class Tests the deprecated legacy route.
 */
#[Group('integration')]
final class ImageResizeControllerTest extends TestCase
{
    use TemporaryDirectoryTrait;

    private CopyingImageConverter $converter;

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function rejectedRequestProvider(): array
    {
        return [
            'missing file'         => [['folder' => 'gallery', 'dimensions' => '20x', 'filename' => 'none.png']],
            'path traversal'       => [['folder' => '..', 'dimensions' => '20x', 'filename' => 'secret.txt']],
            'encoded traversal'    => [['folder' => '%2E%2E', 'dimensions' => '20x', 'filename' => 'secret.txt']],
            'malformed dimensions' => [['folder' => 'gallery', 'dimensions' => '20;rm', 'filename' => 'photo.png']],
            'no route parameters'  => [[]],
        ];
    }

    #[Test]
    public function factoryTakesTheResizerFromTheContainer(): void
    {
        $container = new InMemoryContainer([LegacyResizer::class => $this->resizer()]);

        static::assertInstanceOf(ImageResizeController::class, (new ImageResizeControllerFactory())($container));
    }

    #[Test]
    public function generatesAndServesTheResizedImage(): void
    {
        $response = $this->dispatch(['folder' => 'gallery', 'dimensions' => '20x', 'filename' => 'photo.png']);

        static::assertSame(
            [200, 'image/png', true, '20x'],
            [
                $response->getStatusCode(),
                $response->getHeaders()->get('Content-Type')?->getFieldValue(),
                is_file($this->path('asset/gallery/.20x/photo.png')),
                $this->converter->calls[0]['operations'][4] ?? null,
            ],
        );
    }

    #[Test]
    public function reportsADestinationThatCannotBeCreated(): void
    {
        $this->writeFile('asset/gallery/.20x', 'a file where the directory should be');

        $response = $this->dispatch(['folder' => 'gallery', 'dimensions' => '20x', 'filename' => 'photo.png']);

        static::assertSame([500, true], [
            $response->getStatusCode(),
            str_contains((string) $response->getContent(), 'Cannot create'),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    #[DataProvider('rejectedRequestProvider')]
    #[Test]
    public function respondsNotFoundForUnsafeOrMissingFiles(array $params): void
    {
        static::assertSame([404, []], [$this->dispatch($params)->getStatusCode(), $this->converter->calls]);
    }

    #[Test]
    public function respondsNotImplementedWithoutAConverter(): void
    {
        $response = $this->dispatch(
            ['folder' => 'gallery', 'dimensions' => '20x', 'filename' => 'photo.png'],
            converter: null,
        );

        static::assertSame(501, $response->getStatusCode());
    }

    #[Test]
    public function servesAnExistingResizeWithoutConverting(): void
    {
        $this->writeFile('asset/gallery/.20x/photo.png', 'cached');

        $response = $this->dispatch(['folder' => 'gallery', 'dimensions' => '20x', 'filename' => 'photo.png']);

        static::assertSame(['cached', []], [$response->getContent(), $this->converter->calls]);
    }

    protected function setUp(): void
    {
        $this->setUpTemporaryDirectory();
        $this->converter = new CopyingImageConverter();
        $this->writePng('asset/gallery/photo.png', 40, 20);
        $this->writeFile('secret.txt', 'top secret');
    }

    protected function tearDown(): void
    {
        $this->tearDownTemporaryDirectory();
    }

    /**
     * @param array<string, string> $params
     */
    private function dispatch(array $params, ?CopyingImageConverter $converter = new CopyingImageConverter()): Response
    {
        $this->converter = $converter ?? $this->converter;
        $controller      = new ImageResizeController(new LegacyResizer($this->path(), $converter));
        $controller->getEvent()->setRouteMatch(new RouteMatch($params));

        return $controller->resizeAction();
    }

    /**
     * @mago-expect analysis:deprecated-class Builds the deprecated legacy resizer under test.
     */
    private function resizer(): LegacyResizer
    {
        return new LegacyResizer($this->path(), $this->converter);
    }
}
