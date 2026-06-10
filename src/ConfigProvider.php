<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc;

use Contenir\Storage\Image\ImageResizer;
use Laminas\Router\Http\Regex;

/**
 * Framework-neutral-ish config for the Laminas MVC asset adapter. The {@see Module}
 * returns this from getConfig(); kept separate so the wiring is testable and a
 * Mezzio sibling could reuse the dependency map later.
 */
final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'storage'         => $this->getStorageDefaults(),
            'router'          => $this->getRouteConfig(),
            'controllers'     => $this->getControllerConfig(),
            'service_manager' => $this->getServiceConfig(),
            'view_helpers'    => $this->getViewHelperConfig(),
        ];
    }

    /**
     * On-disk + URL defaults for the local `asset` profile. Override per-site in
     * config/autoload/storage.global.php.
     *
     * @return array<string, mixed>
     */
    public function getStorageDefaults(): array
    {
        return [
            'asset' => [
                'root_path'       => 'public',
                'public_path'     => '',
                'variant_widths'  => [320, 480, 600, 760, 960, 1280, 1440, 1920, 2560],
                'variant_formats' => ['avif', 'webp'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteConfig(): array
    {
        return [
            'routes' => [
                'assetvariant' => [
                    'type'    => Regex::class,
                    'options' => [
                        'regex'    => '/asset/(?<folder>.+?)/_variant/(?<dimensions>[\d.]*x[\d.]*)/(?<filename>[^/]+)',
                        'defaults' => [
                            'controller' => Controller\AssetVariantController::class,
                            'action'     => 'index',
                        ],
                        'spec'     => '/asset/%folder%/_variant/%dimensions%/%filename%',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getControllerConfig(): array
    {
        return [
            'factories' => [
                Controller\AssetVariantController::class => Controller\Factory\AssetVariantControllerFactory::class,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                Service\AssetUrlBuilder::class   => Service\Factory\AssetUrlBuilderFactory::class,
                Service\VariantGenerator::class  => Service\Factory\VariantGeneratorFactory::class,
                ImageResizer::class              => Service\Factory\ImageResizerFactory::class,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewHelperConfig(): array
    {
        return [
            'aliases'   => [
                'storageUrl'     => View\Helper\StorageUrl::class,
                'StorageUrl'     => View\Helper\StorageUrl::class,
                'storageSrcSet'  => View\Helper\StorageSrcSet::class,
                'StorageSrcSet'  => View\Helper\StorageSrcSet::class,
                'storageSources' => View\Helper\StorageSources::class,
                'StorageSources' => View\Helper\StorageSources::class,
            ],
            'factories' => [
                View\Helper\StorageUrl::class     => View\Helper\Factory\StorageUrlFactory::class,
                View\Helper\StorageSrcSet::class  => View\Helper\Factory\StorageSrcSetFactory::class,
                View\Helper\StorageSources::class => View\Helper\Factory\StorageSourcesFactory::class,
            ],
        ];
    }
}
