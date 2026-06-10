<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Integration\Service;

use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Contenir\Storage\Exception\WriteException;
use Contenir\Storage\Image\ImageResizer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
final class VariantGeneratorTest extends TestCase
{
    private string $root;
    private VariantGenerator $generator;

    protected function setUp(): void
    {
        if (! \function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('GD extension required to build the source fixture.');
        }

        try {
            $resizer = new ImageResizer();
        } catch (WriteException) {
            self::markTestSkipped('ImageMagick binary not available.');
        }

        $this->root = sys_get_temp_dir() . '/cav-' . uniqid('', true);
        $dir        = $this->root . '/asset/library/sample';
        mkdir($dir, 0o777, true);

        $image = imagecreatetruecolor(1600, 1200);
        imagefilledrectangle($image, 0, 0, 1600, 1200, imagecolorallocate($image, 120, 40, 200));
        imagejpeg($image, $dir . '/photo.jpg', 90);
        imagedestroy($image);

        $this->generator = new VariantGenerator($resizer, $this->root);
    }

    protected function tearDown(): void
    {
        if (isset($this->root) && is_dir($this->root)) {
            $this->removeTree($this->root);
        }
    }

    public function testGeneratesWidthBoundVariantInSourceFormat(): void
    {
        $path = $this->generator->generate('library/sample', '480x', 'photo.jpg');

        self::assertNotNull($path);
        self::assertFileExists($path);
        self::assertStringEndsWith('/asset/library/sample/_variant/480x/photo.jpg', $path);

        [$width] = getimagesize($path);
        self::assertSame(480, $width);
    }

    public function testGeneratesWebpVariantFromJpegSource(): void
    {
        $path = $this->generator->generate('library/sample', '480x', 'photo.webp');

        self::assertNotNull($path);
        self::assertFileExists($path);
        self::assertStringEndsWith('.webp', $path);
        self::assertSame('image/webp', $this->detectMime($path));
    }

    public function testHeightBoundVariantConstrainsHeight(): void
    {
        $path = $this->generator->generate('library/sample', 'x300', 'photo.jpg');

        self::assertNotNull($path);
        [, $height] = getimagesize($path);
        self::assertSame(300, $height);
    }

    public function testBothAxesCropToExactDimensions(): void
    {
        $path = $this->generator->generate('library/sample', '400x400', 'photo.jpg');

        self::assertNotNull($path);
        [$width, $height] = getimagesize($path);
        self::assertSame(400, $width);
        self::assertSame(400, $height);
    }

    public function testReturnsExistingVariantWithoutRegenerating(): void
    {
        $first = $this->generator->generate('library/sample', '480x', 'photo.jpg');
        self::assertNotNull($first);

        $mtime  = filemtime($first);
        $second = $this->generator->generate('library/sample', '480x', 'photo.jpg');

        self::assertSame($first, $second);
        self::assertSame($mtime, filemtime($second));
    }

    public function testReturnsNullWhenSourceMissing(): void
    {
        self::assertNull($this->generator->generate('library/sample', '480x', 'nope.jpg'));
    }

    public function testReturnsNullForEmptyDimensions(): void
    {
        self::assertNull($this->generator->generate('library/sample', 'x', 'photo.jpg'));
    }

    private function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $path);
        finfo_close($finfo);

        return (string) $mime;
    }

    private function removeTree(string $dir): void
    {
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeTree($path) : unlink($path);
        }
        rmdir($dir);
    }
}
