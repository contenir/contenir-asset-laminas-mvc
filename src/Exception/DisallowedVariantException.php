<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Exception;

use Contenir\Storage\Config\PathVariantResolver;
use RuntimeException;

use function sprintf;

/**
 * Thrown when a template requests a variant the asset's path does not own.
 *
 * Path → family ownership is the single source of truth (`storage.paths`); a
 * mismatch means the template and the config disagree. Throwing surfaces it —
 * the registered error handler renders and logs it per environment.
 */
final class DisallowedVariantException extends RuntimeException
{
    public static function for(string $path, string $variant): self
    {
        return new self(sprintf(
            'Path "%s" does not own variant "%s" (family "%s"). Add the family to this path '
            . '(or "*") in storage.paths, or fix the template.',
            $path,
            $variant,
            PathVariantResolver::family($variant),
        ));
    }
}
