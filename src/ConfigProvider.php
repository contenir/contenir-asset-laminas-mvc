<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc;

use Contenir\Storage\Image\ImageResizer;
use Laminas\Router\Http\Regex;

/**
 * Framework wiring for the keyed asset bridge. The {@see Module} returns this
 * from getConfig(); kept separate so the wiring is testable and a Mezzio sibling
 * could reuse the dependency map later.
 *
 * Variant behaviour (widths, crop, quality, sizes, formats) is NOT defined here:
 * it lives in the shared `settings.storage.profiles` config that both this
 * package and the CMS read. This provider only supplies the on-disk/URL base
 * (`storage.asset`) and the dependency map.
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
     * On-disk + URL base for locating originals and prefixing URLs. Override per
     * site in config/autoload/storage.global.php.
     *
     * @return array<string, mixed>
     */
    public function getStorageDefaults(): array
    {
        return [
            'asset' => [
                'root_path'   => 'public',
                'public_path' => '',
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
                        'regex'    => '/asset/(?<folder>.+?)/_variant/(?<name>[A-Za-z0-9_-]+)/(?<filename>[^/]+)',
                        'defaults' => [
                            'controller' => Controller\AssetVariantController::class,
                            'action'     => 'index',
                        ],
                        'spec'     => '/asset/%folder%/_variant/%name%/%filename%',
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
                Service\ProfileProviderService::class => Service\Factory\ProfileProviderServiceFactory::class,
                Service\AssetUrlBuilder::class        => Service\Factory\AssetUrlBuilderFactory::class,
                Service\VariantGenerator::class       => Service\Factory\VariantGeneratorFactory::class,
                ImageResizer::class                   => Service\Factory\ImageResizerFactory::class,
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
                'storageSizes'   => View\Helper\StorageSizes::class,
                'StorageSizes'   => View\Helper\StorageSizes::class,
            ],
            'factories' => [
                View\Helper\StorageUrl::class     => View\Helper\Factory\StorageUrlFactory::class,
                View\Helper\StorageSrcSet::class  => View\Helper\Factory\StorageSrcSetFactory::class,
                View\Helper\StorageSources::class => View\Helper\Factory\StorageSourcesFactory::class,
                View\Helper\StorageSizes::class   => View\Helper\Factory\StorageSizesFactory::class,
            ],
        ];
    }
}
