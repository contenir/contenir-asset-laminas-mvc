<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Integration;

use Contenir\Asset\Laminas\Mvc\Command\VariantsCommand;
use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantController;
use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantGenerateController;
use Contenir\Asset\Laminas\Mvc\Module;
use Contenir\Storage\Image\ImageResizer;
use Contenir\Storage\Image\ImageResizerInterface;
use Contenir\Storage\StorageManager;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Wires the module's configuration into real laminas-servicemanager,
 * controller and view-helper plugin managers.
 */
#[Group('integration')]
final class ModuleWiringTest extends TestCase
{
    private ServiceManager $services;

    /** @var array<string, mixed> */
    private array $config;

    #[Test]
    public function controllersResolveThroughTheControllerManager(): void
    {
        $controllers = new ControllerManager($this->services, $this->config['controllers']);

        static::assertInstanceOf(AssetVariantController::class, $controllers->get(AssetVariantController::class));
        static::assertInstanceOf(
            AssetVariantGenerateController::class,
            $controllers->get(AssetVariantGenerateController::class),
        );
    }

    #[Test]
    public function resizerInterfaceResolvesToTheSharedResizer(): void
    {
        static::assertSame(
            $this->services->get(ImageResizer::class),
            $this->services->get(ImageResizerInterface::class),
        );
    }

    #[Test]
    public function servicesResolveThroughTheServiceManager(): void
    {
        static::assertInstanceOf(VariantsCommand::class, $this->services->get(VariantsCommand::class));
        static::assertInstanceOf(ImageResizer::class, $this->services->get(ImageResizer::class));
    }

    #[Test]
    public function viewHelpersRenderThroughTheHelperPluginManager(): void
    {
        $helpers = new HelperPluginManager($this->services, $this->config['view_helpers']);

        $srcSet = $helpers->get('storageSrcSet');
        $sizes  = $helpers->get('storageSizes');
        $url    = $helpers->get('storageUrl');

        static::assertSame('/media/news/_variant/card-320/a.jpg 320w', $srcSet('/media/news/a.jpg', profile: 'card'));
        static::assertSame('100vw', $sizes('card'));
        static::assertSame('/media/news/a.jpg', $url('news/a.jpg'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->config                       = (new Module())->getConfig();
        $this->config['storage']['backend'] = ['local' => [
            'type'        => 'local',
            'public_path' => '/media',
            'binary'      => '/bin/false',
        ]];
        $this->config['storage']['variants'] = ['card' => ['dimensions' => ['320x'], 'sizes' => '100vw']];
        $this->services                      = new ServiceManager($this->config['service_manager']);
        $this->services->setService('config', $this->config);
        $this->services->setService(StorageManager::class, new StorageManager());
        $this->services->setService('EventManager', new EventManager(new SharedEventManager()));
        $this->services->setService('SharedEventManager', new SharedEventManager());
        $this->services->setService('ControllerPluginManager', new PluginManager($this->services));
    }
}
