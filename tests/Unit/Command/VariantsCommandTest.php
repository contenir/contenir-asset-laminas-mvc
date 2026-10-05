<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\Command;

use Contenir\Asset\Laminas\Mvc\Command\VariantsCommand;
use Contenir\Asset\Laminas\Mvc\Tests\TestAsset\FakeOnDemandStorage;
use Contenir\Storage\Entry;
use Contenir\Storage\MissingVariantsReporterInterface;
use Contenir\Storage\StorageInterface;
use Contenir\Storage\StorageManager;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function basename;

#[Group('unit')]
final class VariantsCommandTest extends TestCase
{
    #[Test]
    public function aFailingOriginalFailsTheRunButNotTheRest(): void
    {
        $storage = $this->createStubForIntersectionOfInterfaces([
            StorageInterface::class,
            MissingVariantsReporterInterface::class,
        ]);
        $storage->method('list')->willReturn([$this->entry('a.jpg'), $this->entry('b.jpg')]);
        $storage->method('missingVariants')->willReturnCallback(static fn(string $path): array => (
            'a.jpg' === $path ? throw new RuntimeException('bucket on fire') : []
        ));
        $tester = $this->tester($storage);

        $tester->execute([]);

        static::assertSame(1, $tester->getStatusCode());
        static::assertStringContainsString('fail a.jpg: bucket on fire', $tester->getDisplay());
    }

    #[Test]
    public function anOriginalDeletedMidRunIsNotAFailure(): void
    {
        // list() and the per-original call are not atomic, so a concurrent
        // delete must not fail the whole run.
        $storage = new FakeOnDemandStorage(['gone.jpg'], [], ['gone.jpg']);
        $tester  = $this->tester($storage);

        $tester->execute(['--generate' => true]);

        static::assertSame(0, $tester->getStatusCode());
    }

    #[Test]
    public function defaultsToThePrimaryBackendWhenNoneIsNamed(): void
    {
        // The old default named a backend ('assets') that the flat schema no
        // longer produces, so every run failed on an unknown backend.
        $storage = new FakeOnDemandStorage(['gallery/cat.jpg']);
        $tester  = $this->tester($storage, 'r2');

        $tester->execute([]);

        static::assertSame(0, $tester->getStatusCode());
        static::assertStringContainsString('backend=r2', $tester->getDisplay());
    }

    #[Test]
    public function failsWhenTheBackendCannotReportAndGenerationWasNotAsked(): void
    {
        $tester = $this->tester($this->createStub(StorageInterface::class));

        $tester->execute([]);

        static::assertSame(1, $tester->getStatusCode());
        static::assertStringContainsString('Backend "r2" cannot report missing variants.', $tester->getDisplay());
    }

    #[Test]
    public function failsWhenTheNamedBackendIsUnknown(): void
    {
        $tester = $this->tester(new FakeOnDemandStorage([]));

        $tester->execute(['--backend' => 'nope']);

        static::assertSame(1, $tester->getStatusCode());
        static::assertStringContainsString('Unknown backend "nope"', $tester->getDisplay());
    }

    #[Test]
    public function generatesEveryOutstandingVariantWhenAsked(): void
    {
        $storage = new FakeOnDemandStorage(
            ['gallery/cat.jpg'],
            ['gallery/cat.jpg' => ['gallery/cat__hero-480.jpg', 'gallery/cat__hero-480.avif']],
        );
        $tester = $this->tester($storage);

        $tester->execute(['--generate' => true]);

        static::assertSame(
            ['gallery/cat__hero-480.jpg', 'gallery/cat__hero-480.avif'],
            $storage->generated,
        );
    }

    #[Test]
    public function generatesNothingForAnAlreadyCompleteOriginal(): void
    {
        $storage = new FakeOnDemandStorage(['gallery/cat.jpg']);
        $tester  = $this->tester($storage);

        $tester->execute(['--generate' => true]);

        static::assertSame([], $storage->generated);
        static::assertStringNotContainsString('Re-run with --generate', $tester->getDisplay());
    }

    #[Test]
    public function generatesThroughABackendThatCannotReport(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('list')->willReturn([$this->entry('a.jpg')]);
        $storage->expects(self::once())->method('regenerateMissingVariants')->with('a.jpg')->willReturn(['a__t.jpg']);
        $tester = $this->tester($storage);

        $tester->execute(['--generate' => true], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertStringContainsString('made a__t.jpg', $tester->getDisplay());
    }

    #[Test]
    public function listsMissingKeysVerboselyAndShowsThePrefix(): void
    {
        $storage = new FakeOnDemandStorage([], ['a.jpg' => ['a__t.jpg']]);
        $tester  = $this->tester($this->walkingStorage($storage));

        $tester->execute(['--prefix' => 'gallery'], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertStringContainsString('prefix=gallery', $tester->getDisplay());
        static::assertStringContainsString('missing gallery/sub/a.jpg', $tester->getDisplay());
    }

    #[Test]
    public function recursesIntoDirectoriesAndSkipsVariantsAndNonImages(): void
    {
        $storage = $this->walkingStorage(new FakeOnDemandStorage([]));
        $tester  = $this->tester($storage);

        $tester->execute(['--prefix' => 'gallery', '--generate' => true]);

        static::assertMatchesRegularExpression('/^\s+1\s+1\s+0\s+0\s*$/m', $tester->getDisplay());
    }

    #[Test]
    public function reportsOutstandingVariantsWithoutGenerating(): void
    {
        $storage = new FakeOnDemandStorage(
            ['gallery/cat.jpg'],
            ['gallery/cat.jpg' => ['gallery/cat__hero-480.jpg', 'gallery/cat__hero-480.avif']],
        );
        $tester = $this->tester($storage);

        $tester->execute([]);

        static::assertSame([], $storage->generated);
        static::assertSame(0, $tester->getStatusCode());
        static::assertStringContainsString('Re-run with --generate', $tester->getDisplay());
    }

    #[Test]
    public function reportsProgressEveryHundredOriginals(): void
    {
        $originals = [];
        for ($i = 0; $i < 100; ++$i) {
            $originals[] = "{$i}.jpg";
        }
        $tester = $this->tester(new FakeOnDemandStorage($originals));

        $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        static::assertStringContainsString('… 100 originals', $tester->getDisplay());
    }

    #[Test]
    public function stopsAfterTheOriginalLimit(): void
    {
        $storage = new FakeOnDemandStorage(
            ['a.jpg', 'b.jpg', 'c.jpg'],
            [
                'a.jpg' => ['a__hero-480.jpg'],
                'b.jpg' => ['b__hero-480.jpg'],
                'c.jpg' => ['c__hero-480.jpg'],
            ],
        );
        $tester = $this->tester($storage);

        $tester->execute(['--generate' => true, '--limit' => '2']);

        static::assertSame(['a__hero-480.jpg', 'b__hero-480.jpg'], $storage->generated);
    }

    #[Test]
    public function treatsANonStringLimitAsNoLimit(): void
    {
        $storage = new FakeOnDemandStorage(['a.jpg', 'b.jpg'], ['a.jpg' => ['x'], 'b.jpg' => ['y']]);
        $tester  = $this->tester($storage);

        $tester->execute(['--generate' => true, '--limit' => 1]);

        static::assertSame(['x', 'y'], $storage->generated);
    }

    private function entry(string $path, bool $isDir = false, string $mime = 'image/jpeg'): Entry
    {
        return new Entry($path, basename($path), $path, $isDir, 1, new DateTimeImmutable('@0'), $mime);
    }

    private function tester(StorageInterface $storage, string $name = 'r2'): CommandTester
    {
        $manager = new StorageManager();
        $manager->register($name, $storage, isPrimary: true);

        return new CommandTester(new VariantsCommand($manager));
    }

    /**
     * A storage whose `gallery` prefix holds a sub-directory with one original,
     * a variant sibling and a non-image; reporting delegates to $reporter.
     */
    private function walkingStorage(FakeOnDemandStorage $reporter): StorageInterface
    {
        $storage = $this->createStubForIntersectionOfInterfaces([
            StorageInterface::class,
            MissingVariantsReporterInterface::class,
        ]);
        $storage->method('list')
            ->willReturnCallback(fn(string $path): array => match ($path) {
                'gallery'     => [$this->entry('gallery/sub', isDir: true, mime: 'inode/directory')],
                'gallery/sub' => [
                    $this->entry('gallery/sub/a.jpg'),
                    $this->entry('gallery/sub/a__t.jpg'),
                    $this->entry('gallery/sub/notes.txt', mime: 'text/plain'),
                ],
                default       => [],
            });
        $storage->method('missingVariants')
            ->willReturnCallback(
                static fn(string $path): array => $reporter->missingVariants(basename($path)) === [] ? [] : [$path],
            );
        $storage->method('regenerateMissingVariants')->willReturn([]);

        return $storage;
    }
}
