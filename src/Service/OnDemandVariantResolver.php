<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service;

use Contenir\Storage\OnDemandVariantGeneratorInterface;
use Contenir\Storage\StorageManager;

/**
 * Resolves an edge/origin request for a sibling variant key to a freshly
 * generated object, by delegating to whichever registered storage backend owns
 * the variant.
 *
 * Variant names are globally unique across profiles, so at most one backend
 * recognises a given key; the rest decline cheaply (no network) at their
 * registry check. Backends that cannot generate on demand are skipped.
 */
final class OnDemandVariantResolver
{
    public function __construct(private StorageManager $manager)
    {
    }

    /**
     * Materialise the variant for $variantKey and return its public URL, or null
     * when no registered backend can produce it.
     */
    public function generate(string $variantKey): ?string
    {
        foreach ($this->manager->profiles() as $profile) {
            $backend = $this->manager->get($profile);
            if (! $backend instanceof OnDemandVariantGeneratorInterface) {
                continue;
            }

            $url = $backend->generateForKey($variantKey);
            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }
}
