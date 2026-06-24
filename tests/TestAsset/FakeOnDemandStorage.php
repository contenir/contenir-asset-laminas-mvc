<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\TestAsset;

use Contenir\Storage\Entry;
use Contenir\Storage\ImageMeta;
use Contenir\Storage\ListOptions;
use Contenir\Storage\OnDemandVariantGeneratorInterface;
use Contenir\Storage\StorageInterface;
use Contenir\Storage\UploadInput;
use DateTimeImmutable;
use LogicException;

use function basename;

/**
 * In-memory storage double exercising only what {@see \Contenir\Asset\Laminas\Mvc\Command\VariantsCommand}
 * touches: a flat list() of originals, exists(), and on-demand generateForKey().
 * The remaining StorageInterface methods are not exercised and throw.
 */
final class FakeOnDemandStorage implements StorageInterface, OnDemandVariantGeneratorInterface
{
    /** @var array<string, true> */
    private array $existing = [];

    /** @var list<string> Keys passed to generateForKey, in order. */
    public array $generated = [];

    /**
     * @param list<string> $originals        Original image keys to enumerate.
     * @param list<string> $existingVariants  Variant keys that already exist.
     */
    public function __construct(private array $originals, array $existingVariants = [])
    {
        foreach ([...$originals, ...$existingVariants] as $key) {
            $this->existing[$key] = true;
        }
    }

    public function list(string $path, ?ListOptions $options = null): iterable
    {
        if ($path !== '') {
            return;
        }
        foreach ($this->originals as $key) {
            yield new Entry($key, basename($key), $key, false, 1, new DateTimeImmutable('@0'), 'image/jpeg');
        }
    }

    public function exists(string $path): bool
    {
        return isset($this->existing[$path]);
    }

    public function generateForKey(string $variantKey): ?string
    {
        $this->generated[]            = $variantKey;
        $this->existing[$variantKey]  = true;

        return 'https://cdn.test/' . $variantKey;
    }

    public function store(UploadInput $upload, string $directory): Entry
    {
        throw new LogicException('not exercised');
    }

    public function url(string $path, ?string $variant = null): ?string
    {
        throw new LogicException('not exercised');
    }

    /** @return array<string, string> */
    public function urlsForKey(string $path): array
    {
        throw new LogicException('not exercised');
    }

    /** @return array<string, string> */
    public function variantUrls(string $path, string $variantName): array
    {
        throw new LogicException('not exercised');
    }

    public function delete(string $path): void
    {
        throw new LogicException('not exercised');
    }

    public function rename(string $from, string $to): void
    {
        throw new LogicException('not exercised');
    }

    public function imageMeta(string $path): ImageMeta
    {
        throw new LogicException('not exercised');
    }

    public function thumbnailUrl(string $path): ?string
    {
        throw new LogicException('not exercised');
    }

    /** @return list<string> */
    public function regenerateMissingVariants(string $path): array
    {
        throw new LogicException('not exercised');
    }
}
