<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\Command;

use Contenir\Asset\Laminas\Mvc\Command\VariantsCommand;
use Contenir\Asset\Laminas\Mvc\Tests\TestAsset\FakeOnDemandStorage;
use Contenir\Storage\StorageManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[Group('unit')]
final class VariantsCommandTest extends TestCase
{
    private function tester(FakeOnDemandStorage $storage): CommandTester
    {
        $manager = new StorageManager();
        $manager->register('assets', $storage);

        // Mirrors a real site: the storage (generation) profile + its variant
        // registry live under storage.profiles.<name>, NOT settings.storage.profiles
        // (which holds the front-end art-directed profiles).
        $config = [
            'storage' => [
                'profiles' => [
                    'assets' => [
                        'variants' => [
                            'hero-480' => ['width' => 480],
                            'card-320' => ['width' => 320],
                        ],
                    ],
                ],
            ],
        ];

        return new CommandTester(new VariantsCommand($manager, $config));
    }

    public function testReportsMissingWithoutGenerating(): void
    {
        $storage = new FakeOnDemandStorage(['gallery/cat.jpg']);
        $tester  = $this->tester($storage);

        $tester->execute(['--profile' => 'assets']);

        // 2 variants × {jpg source, avif, webp} = 6 missing; nothing generated.
        self::assertSame([], $storage->generated);
        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Re-run with --generate', $tester->getDisplay());
    }

    public function testGeneratesSourcePlusModernForEveryVariant(): void
    {
        $storage = new FakeOnDemandStorage(['gallery/cat.jpg']);
        $tester  = $this->tester($storage);

        $tester->execute(['--profile' => 'assets', '--generate' => true]);

        self::assertCount(6, $storage->generated);
        self::assertContains('gallery/cat__hero-480.jpg', $storage->generated, 'source <img> fallback');
        self::assertContains('gallery/cat__hero-480.avif', $storage->generated);
        self::assertContains('gallery/cat__hero-480.webp', $storage->generated);
        self::assertContains('gallery/cat__card-320.avif', $storage->generated);
    }

    public function testVariantAndFormatScoping(): void
    {
        $storage = new FakeOnDemandStorage(['gallery/cat.jpg']);
        $tester  = $this->tester($storage);

        $tester->execute([
            '--profile'  => 'assets',
            '--variant'  => 'hero-480',
            '--format'   => 'avif',
            '--generate' => true,
        ]);

        // hero-480 only; formats = source(jpg) + avif.
        self::assertSame(
            ['gallery/cat__hero-480.jpg', 'gallery/cat__hero-480.avif'],
            $storage->generated,
        );
    }

    public function testSkipsVariantsThatAlreadyExist(): void
    {
        $storage = new FakeOnDemandStorage(
            ['gallery/cat.jpg'],
            ['gallery/cat__hero-480.avif'],
        );
        $tester = $this->tester($storage);

        $tester->execute([
            '--profile'  => 'assets',
            '--variant'  => 'hero-480',
            '--format'   => 'avif',
            '--generate' => true,
        ]);

        // avif already present → only the source jpg is generated.
        self::assertSame(['gallery/cat__hero-480.jpg'], $storage->generated);
    }

    public function testExpandsDimensionFamilyToRungVariants(): void
    {
        $storage = new FakeOnDemandStorage(['gallery/cat.jpg']);
        $manager = new StorageManager();
        $manager->register('assets', $storage);
        $config = [
            'storage' => ['profiles' => ['assets' => ['variants' => [
                'card' => ['fit' => 'cover', 'dimensions' => ['320x320', '480x480']],
            ]]]],
        ];
        $tester = new CommandTester(new VariantsCommand($manager, $config));

        $tester->execute(['--profile' => 'assets', '--format' => 'avif', '--generate' => true]);

        // The family expands to card-320 + card-480, each as {jpg source, avif}.
        self::assertContains('gallery/cat__card-320.avif', $storage->generated);
        self::assertContains('gallery/cat__card-480.avif', $storage->generated);
        self::assertContains('gallery/cat__card-320.jpg', $storage->generated);
        self::assertCount(4, $storage->generated);
    }
}
