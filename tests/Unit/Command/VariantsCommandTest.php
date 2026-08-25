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
    private function tester(FakeOnDemandStorage $storage, string $name = 'r2'): CommandTester
    {
        $manager = new StorageManager();
        $manager->register($name, $storage, isPrimary: true);

        return new CommandTester(new VariantsCommand($manager));
    }

    public function testReportsOutstandingVariantsWithoutGenerating(): void
    {
        $storage = new FakeOnDemandStorage(
            ['gallery/cat.jpg'],
            ['gallery/cat.jpg' => ['gallery/cat__hero-480.jpg', 'gallery/cat__hero-480.avif']],
        );
        $tester = $this->tester($storage);

        $tester->execute([]);

        self::assertSame([], $storage->generated);
        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Re-run with --generate', $tester->getDisplay());
    }

    public function testGeneratesEveryOutstandingVariantWhenAsked(): void
    {
        $storage = new FakeOnDemandStorage(
            ['gallery/cat.jpg'],
            ['gallery/cat.jpg' => ['gallery/cat__hero-480.jpg', 'gallery/cat__hero-480.avif']],
        );
        $tester = $this->tester($storage);

        $tester->execute(['--generate' => true]);

        self::assertSame(
            ['gallery/cat__hero-480.jpg', 'gallery/cat__hero-480.avif'],
            $storage->generated,
        );
    }

    public function testGeneratesNothingForAnAlreadyCompleteOriginal(): void
    {
        $storage = new FakeOnDemandStorage(['gallery/cat.jpg']);
        $tester  = $this->tester($storage);

        $tester->execute(['--generate' => true]);

        self::assertSame([], $storage->generated);
        self::assertStringNotContainsString('Re-run with --generate', $tester->getDisplay());
    }

    public function testDefaultsToThePrimaryBackendWhenNoneIsNamed(): void
    {
        // The old default named a backend ('assets') that the flat schema no
        // longer produces, so every run failed on an unknown backend.
        $storage = new FakeOnDemandStorage(['gallery/cat.jpg']);
        $tester  = $this->tester($storage, 'r2');

        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('backend=r2', $tester->getDisplay());
    }

    public function testFailsWhenTheNamedBackendIsUnknown(): void
    {
        $tester = $this->tester(new FakeOnDemandStorage([]));

        $tester->execute(['--backend' => 'nope']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Unknown backend "nope"', $tester->getDisplay());
    }

    public function testStopsAfterTheOriginalLimit(): void
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

        self::assertSame(['a__hero-480.jpg', 'b__hero-480.jpg'], $storage->generated);
    }

    public function testAnOriginalDeletedMidRunIsNotAFailure(): void
    {
        // list() and the per-original call are not atomic, so a concurrent
        // delete must not fail the whole run.
        $storage = new FakeOnDemandStorage(['gone.jpg'], [], ['gone.jpg']);
        $tester  = $this->tester($storage);

        $tester->execute(['--generate' => true]);

        self::assertSame(0, $tester->getStatusCode());
    }
}
