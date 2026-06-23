<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\Service;

use Contenir\Asset\Laminas\Mvc\Service\OnDemandVariantResolver;
use Contenir\Storage\Adapter\InMemoryStorage;
use Contenir\Storage\Adapter\S3;
use Contenir\Storage\Image\StubImageResizer;
use Contenir\Storage\StorageManager;
use Contenir\Storage\UploadInput;
use Contenir\Storage\Variant;
use Contenir\Storage\VariantFit;
use Contenir\Storage\VariantRegistry;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function imagecreatetruecolor;
use function imagedestroy;
use function imagepng;
use function sys_get_temp_dir;
use function uniqid;

#[Group('unit')]
final class OnDemandVariantResolverTest extends TestCase
{
    public function testGeneratesViaTheOwningOnDemandBackend(): void
    {
        $source = sys_get_temp_dir() . '/odvr_' . uniqid('', true) . '.png';
        $img    = imagecreatetruecolor(20, 20);
        imagepng($img, $source);
        imagedestroy($img);

        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $s3 = new S3(
            fs:            $fs,
            publicUrlBase: 'https://cdn.test',
            variants:      new VariantRegistry(
                new Variant('card-320', 320, 320, VariantFit::Cover, ['avif', 'webp'], 75),
            ),
            resizer:       new StubImageResizer(),
        );
        $s3->store(new UploadInput($source, 'cat.png', 'image/png'), 'gallery');
        $fs->delete('gallery/cat__card-320.avif');

        $manager = new StorageManager();
        $manager->register('assets', $s3);

        $resolver = new OnDemandVariantResolver($manager);

        self::assertSame(
            'https://cdn.test/gallery/cat__card-320.avif',
            $resolver->generate('gallery/cat__card-320.avif'),
        );
        self::assertTrue($fs->fileExists('gallery/cat__card-320.avif'));
    }

    public function testReturnsNullWhenNoBackendOwnsTheVariant(): void
    {
        $manager = new StorageManager();
        $manager->register('assets', new S3(
            fs:            new Filesystem(new InMemoryFilesystemAdapter()),
            publicUrlBase: 'https://cdn.test',
            variants:      new VariantRegistry(new Variant('card-320', 320, 320, VariantFit::Cover)),
            resizer:       new StubImageResizer(),
        ));

        $resolver = new OnDemandVariantResolver($manager);

        self::assertNull($resolver->generate('gallery/cat__unknown-variant.avif'));
    }

    public function testSkipsBackendsThatCannotGenerateOnDemand(): void
    {
        // InMemoryStorage does not implement OnDemandVariantGeneratorInterface,
        // so the resolver must skip it without error and report null.
        $manager = new StorageManager();
        $manager->register('local', new InMemoryStorage());

        $resolver = new OnDemandVariantResolver($manager);

        self::assertNull($resolver->generate('gallery/cat__card-320.avif'));
    }
}
