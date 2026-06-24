<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Command;

use Contenir\Storage\Config\VariantProfile;
use Contenir\Storage\Entry;
use Contenir\Storage\ListOptions;
use Contenir\Storage\OnDemandVariantGeneratorInterface;
use Contenir\Storage\StorageInterface;
use Contenir\Storage\StorageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function implode;
use function is_array;
use function pathinfo;
use function sprintf;
use function str_contains;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

use const PATHINFO_BASENAME;
use const PATHINFO_EXTENSION;

/**
 * Audit — and optionally backfill — the storage variants for every original in a
 * profile's backend.
 *
 * For each original it derives the sibling keys the front-end requests
 * (`<base>__<variant>.<format>`) across the chosen variants and formats — the
 * source extension is always included so the `<img>` fallback resolves, plus the
 * declared/`--format` modern formats for the `<source>`s — and reports which are
 * missing. With `--generate` the missing ones are materialised on demand via the
 * backend's {@see OnDemandVariantGeneratorInterface} (R2/S3). Idempotent and
 * re-runnable; safe to re-run while uploads continue.
 */
final class VariantsCommand extends Command
{
    /** @var array<string, mixed> */
    private array $config;

    private StorageManager $manager;

    /**
     * @param array<string, mixed> $config The merged application config.
     */
    public function __construct(StorageManager $manager, array $config)
    {
        $this->manager = $manager;
        $this->config  = $config;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('storage:variants')
            ->setDescription('Report and optionally backfill missing storage variants for a profile.')
            ->addOption('profile', 'p', InputOption::VALUE_REQUIRED, 'Storage profile to operate on.', 'assets')
            ->addOption('variant', null, InputOption::VALUE_REQUIRED, 'Comma-separated variant name(s) (default: all).')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma-separated modern format(s); the source extension is always included.',
                'avif,webp',
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
        $io          = new SymfonyStyle($input, $output);
        $profileName = (string) $input->getOption('profile');
        $prefix      = (string) $input->getOption('prefix');
        $limit       = (int) $input->getOption('limit');
        $generate    = (bool) $input->getOption('generate');

        $storage = $this->manager->get($profileName);

        $variantNames = $this->resolveVariants($profileName, (string) $input->getOption('variant'));
        if ($variantNames === []) {
            $io->error(sprintf('No variants resolved for profile "%s".', $profileName));

            return Command::FAILURE;
        }

        $modernFormats = array_values(array_filter(array_map(
            static fn (string $f): string => strtolower(trim($f, " \t\n\r\0\x0B.")),
            explode(',', (string) $input->getOption('format')),
        )));

        if ($generate && ! $storage instanceof OnDemandVariantGeneratorInterface) {
            $io->error(sprintf(
                'Profile "%s" (%s) cannot generate on demand. Use --generate only on an R2/S3 profile.',
                $profileName,
                $storage::class,
            ));

            return Command::FAILURE;
        }

        $io->title(sprintf(
            'storage:variants — profile=%s variants=%s formats=source+[%s]%s%s',
            $profileName,
            implode(',', $variantNames),
            implode(',', $modernFormats),
            $prefix !== '' ? " prefix={$prefix}" : '',
            $generate ? ' [GENERATE]' : ' [report only]',
        ));

        $originals = 0;
        $present   = 0;
        $missing   = 0;
        $generated = 0;
        $errors    = 0;

        foreach ($this->eachOriginal($storage, $prefix) as $entry) {
            if ($limit > 0 && $originals >= $limit) {
                break;
            }
            $originals++;

            $base      = $this->stripExtension($entry->path);
            $sourceExt = strtolower((string) pathinfo($entry->path, PATHINFO_EXTENSION));
            $formats   = array_values(array_unique([$sourceExt, ...$modernFormats]));

            foreach ($variantNames as $variantName) {
                foreach ($formats as $format) {
                    $key = sprintf('%s__%s.%s', $base, $variantName, $format);
                    if ($storage->exists($key)) {
                        $present++;
                        continue;
                    }
                    $missing++;
                    if (! $generate) {
                        $io->writeln("  <comment>missing</comment> {$key}", OutputInterface::VERBOSITY_VERBOSE);
                        continue;
                    }
                    try {
                        /** @var OnDemandVariantGeneratorInterface $storage */
                        if ($storage->generateForKey($key) !== null) {
                            $generated++;
                            $io->writeln("  <info>made</info> {$key}", OutputInterface::VERBOSITY_VERBOSE);
                        } else {
                            $errors++;
                            $io->writeln("  <error>skip</error> {$key} (not generatable)");
                        }
                    } catch (Throwable $e) {
                        $errors++;
                        $io->writeln(sprintf('  <error>fail</error> %s: %s', $key, $e->getMessage()));
                    }
                }
            }
        }

        $io->newLine();
        $io->table(
            ['originals', 'present', 'missing', $generate ? 'generated' : '—', 'errors'],
            [[$originals, $present, $missing, $generate ? $generated : '—', $errors]],
        );

        if (! $generate && $missing > 0) {
            $io->note('Re-run with --generate to create the missing variants.');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Resolve the variant name list from --variant, or all variants declared for
     * the profile in settings.storage.profiles / storage.profiles.
     *
     * @return list<string>
     */
    private function resolveVariants(string $profileName, string $option): array
    {
        if ($option !== '') {
            return array_values(array_filter(array_map(
                static fn (string $v): string => trim($v),
                explode(',', $option),
            )));
        }

        // --profile names a STORAGE profile (StorageManager::get()), whose
        // variant registry is built from storage.profiles.<name>.variants. Fall
        // back to the front-end settings.storage.profiles for sites that share
        // one namespace.
        $declared = $this->config['storage']['profiles'][$profileName]['variants']
            ?? $this->config['settings']['storage']['profiles'][$profileName]['variants']
            ?? [];
        if (! is_array($declared)) {
            return [];
        }

        $names = [];
        foreach ($declared as $name => $entry) {
            // Art-directed family: expand its `dimensions` ladder to the rung
            // names (card-320, …). Flat entries contribute their own name.
            if (is_array($entry) && isset($entry['dimensions'])) {
                foreach (VariantProfile::fromArray((string) $name, $entry)->variants as $variant) {
                    $names[] = $variant->name;
                }
                continue;
            }
            $names[] = (string) $name;
        }

        return array_values(array_filter($names));
    }

    /**
     * Walk the backend recursively, yielding image-file originals (keys without a
     * `__variant` suffix). list() is one level deep, so recurse directories.
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

    private function stripExtension(string $path): string
    {
        $dot = strrpos($path, '.');

        return $dot === false ? $path : substr($path, 0, $dot);
    }
}
