<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Profile;

use Contenir\Storage\Variant;

/**
 * Immutable view of one storage profile from `settings.storage.profiles`, as the
 * front-end needs it: the HTML `sizes` attribute, the extra `<picture>` output
 * formats, and the responsive variants (the CMS preview variant excluded).
 */
final readonly class Profile
{
    /**
     * @param list<string>  $formats  Extra output formats, e.g. ['avif', 'webp'].
     * @param list<Variant> $variants Responsive variants, preview excluded.
     */
    public function __construct(
        public string $key,
        public string $sizes,
        public array $formats,
        public array $variants,
    ) {
    }
}
