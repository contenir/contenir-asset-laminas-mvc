<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Integration\Service;

use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Contenir\Storage\Config\PathVariantResolver;
use Contenir\Storage\Image\ImageResizer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function function_exists;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagejpeg;
use function is_dir;
use function is_string;
use function mkdir;
use function rmdir;
use function scandir;
use function shell_exec;
use function sys_get_temp_dir;
use function trim;
use function uniqid;
use function unlink;

#[Group('integration')]
final class VariantGeneratorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/calmvc-' . uniqid();
        mkdir($this->root . '/asset/foo', 0o775, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    private function generator(): VariantGenerator
    {
        $profiles = new ProfileProviderService([
            'thumb' => ['variants' => ['t-80' => ['width' => 80, 'height' => 0, 'fit' => 'contain']]],
        ], new PathVariantResolver([]));

        return new VariantGenerator(new ImageResizer(), $profiles, $this->root);
    }

    public function testReturnsNullForUnknownVariant(): void
    {
        self::assertNull($this->generator()->generate('foo', 'nope', 'pic.jpg'));
    }

    public function testReturnsNullWhenSourceMissing(): void
    {
        self::assertNull($this->generator()->generate('foo', 't-80', 'missing.jpg'));
    }

    public function testGeneratesVariantFromSource(): void
    {
        if (! function_exists('imagejpeg')) {
            self::markTestSkipped('GD extension not available.');
        }
        if (! $this->hasImageBinary()) {
            self::markTestSkipped('No ImageMagick binary available.');
        }

        $image = imagecreatetruecolor(200, 150);
        imagejpeg($image, $this->root . '/asset/foo/pic.jpg');
        imagedestroy($image);

        $path = $this->generator()->generate('foo', 't-80', 'pic.jpg');

        self::assertNotNull($path);
        self::assertFileExists($path);
        self::assertStringContainsString('/_variant/t-80/', $path);
    }

    private function hasImageBinary(): bool
    {
        $found = shell_exec('command -v magick convert 2>/dev/null');

        return is_string($found) && trim($found) !== '';
    }

    private function removeTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeTree($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
