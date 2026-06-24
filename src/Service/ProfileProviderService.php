<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Service;

use Contenir\Asset\Laminas\Mvc\Profile\Profile;
use Contenir\Storage\Config\VariantProfile;
use Contenir\Storage\Variant;
use Contenir\Storage\VariantFit;

use function array_values;
use function is_array;
use function strtolower;

/**
 * Exposes the art-directed image profiles as typed {@see Profile} / {@see Variant}
 * objects for the front-end helpers.
 *
 * A profile declared with a `dimensions` ladder is compiled by the shared
 * {@see VariantProfile} — the same declaration the generator materialises — so the
 * family lives in a single place. The legacy form (an explicit `variants` map) is
 * still accepted during migration, as is a flat standalone variant (e.g. the
 * `admin-thumb` preview) which is registered for lookup but never exposed as a
 * responsive profile.
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
     * @param array<string, mixed> $profiles Art-directed profile declarations.
     */
    public function __construct(array $profiles)
    {
        foreach ($profiles as $key => $config) {
            if (! is_array($config)) {
                continue;
            }

            if (isset($config['dimensions'])) {
                $this->addFamily((string) $key, $config);
                continue;
            }

            if (isset($config['variants'])) {
                $this->addLegacyProfile((string) $key, $config);
                continue;
            }

            // Flat standalone variant (e.g. the admin-thumb preview): registered
            // for lookup, never a responsive profile.
            if (isset($config['width'])) {
                $this->variants[(string) $key] = $this->buildVariant((string) $key, $config);
            }
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
    private function addFamily(string $key, array $config): void
    {
        $profile = VariantProfile::fromArray($key, $config);
        foreach ($profile->variants as $variant) {
            $this->variants[$variant->name] = $variant;
        }

        if ($profile->isPreview) {
            return;
        }

        $this->profiles[$key] = new Profile(
            $key,
            $profile->sizes,
            $this->formats($config),
            $profile->variants,
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addLegacyProfile(string $key, array $config): void
    {
        $responsive = [];
        foreach ((array) $config['variants'] as $name => $variantConfig) {
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

        $this->profiles[$key] = new Profile(
            $key,
            (string) ($config['sizes'] ?? ''),
            $this->formats($config),
            array_values($responsive),
        );
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return list<string>
     */
    private function formats(array $config): array
    {
        $formats = [];
        foreach ((array) ($config['formats'] ?? []) as $format) {
            $formats[] = strtolower((string) $format);
        }

        return $formats;
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
