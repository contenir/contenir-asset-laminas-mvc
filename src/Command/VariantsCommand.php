<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Command;

use Contenir\Storage\Entry;
use Contenir\Storage\Exception\NotFoundException;
use Contenir\Storage\ListOptions;
use Contenir\Storage\MissingVariantsReporterInterface;
use Contenir\Storage\StorageInterface;
use Contenir\Storage\StorageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function count;
use function implode;
use function pathinfo;
use function sprintf;
use function str_contains;

use const PATHINFO_BASENAME;

/**
 * Audit — and optionally backfill — the storage variants for every original in
 * a backend.
 *
 * What a path is entitled to comes entirely from config: `storage.variants`
 * declares the families, `storage.paths` declares which paths own them, and the
 * backend applies both. The command asks the backend rather than re-deriving
 * the expected keys, so there is one source of truth and no way for tooling and
 * rendering to disagree about what should exist.
 *
 * Reporting uses {@see MissingVariantsReporterInterface::missingVariants()} —
 * no writes, no source download. Generation uses
 * {@see StorageInterface::regenerateMissingVariants()}, which fetches each
 * original once and derives every missing variant from that single copy.
 *
 * Idempotent and re-runnable; safe to re-run while uploads continue.
 */
final class VariantsCommand extends Command
{
    private StorageManager $manager;

    public function __construct(StorageManager $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('storage:variants')
            ->setDescription('Report and optionally backfill missing storage variants for a backend.')
            ->addOption(
                'backend',
                'b',
                InputOption::VALUE_REQUIRED,
                'Storage backend to operate on (default: the primary backend).',
                '',
            )
            ->addOption(
                'prefix',
                null,
                InputOption::VALUE_REQUIRED,
                'Only process originals under this key prefix.',
                '',
            )
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Process at most N originals.', '0')
            ->addOption('generate', 'g', InputOption::VALUE_NONE, 'Generate the missing variants (default: report).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $prefix   = (string) $input->getOption('prefix');
        $limit    = (int) $input->getOption('limit');
        $generate = (bool) $input->getOption('generate');

        $backendName = (string) $input->getOption('backend');
        if ($backendName === '') {
            $backendName = $this->manager->primaryKey();
        }
        if (! $this->manager->has($backendName)) {
            $io->error(sprintf(
                'Unknown backend "%s". Available: %s.',
                $backendName,
                implode(', ', $this->manager->profiles()),
            ));

            return Command::FAILURE;
        }
        $storage = $this->manager->get($backendName);

        if (! $generate && ! $storage instanceof MissingVariantsReporterInterface) {
            $io->error(sprintf('Backend "%s" cannot report missing variants.', $backendName));

            return Command::FAILURE;
        }

        $io->title(sprintf(
            'storage:variants — backend=%s%s%s',
            $backendName,
            $prefix !== '' ? " prefix={$prefix}" : '',
            $generate ? ' [GENERATE]' : ' [report only]',
        ));

        $originals = 0;
        $complete  = 0;
        $missing   = 0;
        $generated = 0;
        $errors    = 0;

        foreach ($this->eachOriginal($storage, $prefix) as $entry) {
            if ($limit > 0 && $originals >= $limit) {
                break;
            }
            $originals++;

            try {
                if ($generate) {
                    $keys = $storage->regenerateMissingVariants($entry->path);
                    $generated += count($keys);
                    foreach ($keys as $key) {
                        $io->writeln("  <info>made</info> {$key}", OutputInterface::VERBOSITY_VERBOSE);
                    }
                } else {
                    /** @var MissingVariantsReporterInterface $storage */
                    $keys     = $storage->missingVariants($entry->path);
                    $missing += count($keys);
                    foreach ($keys as $key) {
                        $io->writeln("  <comment>missing</comment> {$key}", OutputInterface::VERBOSITY_VERBOSE);
                    }
                }

                if ($keys === []) {
                    $complete++;
                }
            } catch (NotFoundException) {
                // Listed a moment ago and gone now — a concurrent delete, not a fault.
                $io->writeln("  <comment>vanished</comment> {$entry->path}", OutputInterface::VERBOSITY_VERBOSE);
            } catch (Throwable $e) {
                $errors++;
                $io->writeln(sprintf('  <error>fail</error> %s: %s', $entry->path, $e->getMessage()));
            }

            if ($originals % 100 === 0) {
                $io->writeln(
                    sprintf('  … %d originals', $originals),
                    OutputInterface::VERBOSITY_VERBOSE,
                );
            }
        }

        $io->newLine();
        $io->table(
            ['originals', 'complete', $generate ? 'generated' : 'missing', 'errors'],
            [[$originals, $complete, $generate ? $generated : $missing, $errors]],
        );

        if (! $generate && $missing > 0) {
            $io->note('Re-run with --generate to create the missing variants.');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Walk the backend recursively, yielding image-file originals (keys without
     * a `__variant` suffix). list() is one level deep, so recurse directories.
     *
     * @return iterable<Entry>
     */
    private function eachOriginal(StorageInterface $storage, string $path): iterable
    {
        $options = new ListOptions(includeDirectories: true);
        foreach ($storage->list($path, $options) as $entry) {
            if ($entry->isDir) {
                yield from $this->eachOriginal($storage, $entry->path);
                continue;
            }
            if ($entry->isImage() && ! str_contains(pathinfo($entry->path, PATHINFO_BASENAME), '__')) {
                yield $entry;
            }
        }
    }
}
