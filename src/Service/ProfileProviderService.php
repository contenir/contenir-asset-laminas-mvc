<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service;

use Contenir\Asset\Laminas\Mvc\Profile\Profile;
use Contenir\Storage\Variant;
use Contenir\Storage\VariantFit;

use function array_values;
use function is_array;
use function strtolower;

/**
 * Reads the shared `settings.storage.profiles` config (the same config the CMS
 * consumes) and exposes it as typed {@see Profile} / {@see Variant} objects.
 *
 * Variant names are globally unique across profiles, so a bare name — all a
 * request URL carries — resolves to exactly one definition.
 */
final class ProfileProviderService
{
    /** The CMS preview variant; never emitted in front-end srcset/sources. */
    public const PREVIEW_VARIANT = 'admin-thumb';

    /** @var array<string, Profile> */
    private array $profiles = [];

    /** @var array<string, Variant> Flat name => variant, across every profile. */
    private array $variants = [];

    /**
     * @param array<string, mixed> $profiles The `settings.storage.profiles` array.
     */
    public function __construct(array $profiles)
    {
        foreach ($profiles as $key => $config) {
            if (! is_array($config)) {
                continue;
            }

            $responsive = [];
            foreach ((array) ($config['variants'] ?? []) as $name => $variantConfig) {
                $name = (string) $name;
                if (! is_array($variantConfig)) {
                    continue;
                }

                $variant               = $this->buildVariant($name, $variantConfig);
                $this->variants[$name] = $variant;
                if ($name !== self::PREVIEW_VARIANT) {
                    $responsive[] = $variant;
                }
            }

            $formats = [];
            foreach ((array) ($config['formats'] ?? []) as $format) {
                $formats[] = strtolower((string) $format);
            }

            $key                  = (string) $key;
            $this->profiles[$key] = new Profile(
                $key,
                (string) ($config['sizes'] ?? ''),
                $formats,
                array_values($responsive),
            );
        }
    }

    public function has(string $key): bool
    {
        return isset($this->profiles[$key]);
    }

    public function get(string $key): ?Profile
    {
        return $this->profiles[$key] ?? null;
    }

    public function variant(string $name): ?Variant
    {
        return $this->variants[$name] ?? null;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildVariant(string $name, array $config): Variant
    {
        return new Variant(
            $name,
            (int) ($config['width'] ?? 0),
            (int) ($config['height'] ?? 0),
            $this->fit(strtolower((string) ($config['fit'] ?? 'cover'))),
            [],
            isset($config['quality']) ? (int) $config['quality'] : null,
        );
    }

    private function fit(string $fit): VariantFit
    {
        return match ($fit) {
            'contain' => VariantFit::Contain,
            'fill'    => VariantFit::Fill,
            default   => VariantFit::Cover,
        };
    }
}
