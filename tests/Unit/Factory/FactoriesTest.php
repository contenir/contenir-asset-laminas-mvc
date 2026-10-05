<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\Factory;

use Contenir\Asset\Laminas\Mvc\Command\VariantsCommand;
use Contenir\Asset\Laminas\Mvc\Command\VariantsCommandFactory;
use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantController;
use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantGenerateController;
use Contenir\Asset\Laminas\Mvc\Controller\Factory\AssetVariantControllerFactory;
use Contenir\Asset\Laminas\Mvc\Controller\Factory\AssetVariantGenerateControllerFactory;
use Contenir\Asset\Laminas\Mvc\Service\AssetUrlBuilder;
use Contenir\Asset\Laminas\Mvc\Service\Factory\AssetUrlBuilderFactory;
use Contenir\Asset\Laminas\Mvc\Service\Factory\ImageResizerFactory;
use Contenir\Asset\Laminas\Mvc\Service\Factory\OnDemandVariantResolverFactory;
use Contenir\Asset\Laminas\Mvc\Service\Factory\ProfileProviderServiceFactory;
use Contenir\Asset\Laminas\Mvc\Service\Factory\VariantGeneratorFactory;
use Contenir\Asset\Laminas\Mvc\Service\OnDemandVariantResolver;
use Contenir\Asset\Laminas\Mvc\Service\ProfileProviderService;
use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Contenir\Asset\Laminas\Mvc\Tests\TestAsset\Container\InMemoryContainer;
use Contenir\Asset\Laminas\Mvc\View\Helper\Factory\StorageSizesFactory;
use Contenir\Asset\Laminas\Mvc\View\Helper\Factory\StorageSourcesFactory;
use Contenir\Asset\Laminas\Mvc\View\Helper\Factory\StorageSrcSetFactory;
use Contenir\Asset\Laminas\Mvc\View\Helper\Factory\StorageUrlFactory;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSizes;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSources;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageSrcSet;
use Contenir\Asset\Laminas\Mvc\View\Helper\StorageUrl;
use Contenir\Storage\Image\ImageResizer;
use Contenir\Storage\Image\StubImageResizer;
use Contenir\Storage\StorageManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class FactoriesTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function urlBuilderProvider(): array
    {
        return [
            'local, no config'      => [[], '/a/_variant/thumb/b.jpg'],
            'local public path'     => [
                ['local' => ['type' => 'local', 'public_path' => '/media']],
                '/media/a/_variant/thumb/b.jpg',
            ],
            's3 public_base_url'    => [
                ['r2' => ['type' => 's3', 'default' => true, 'public_base_url' => 'https://cdn.test']],
                'https://cdn.test/a/b__thumb.jpg',
            ],
            's3 publicUrl fallback' => [
                ['r2' => ['type' => 's3', 'default' => true, 'publicUrl' => 'https://cdn.test']],
                'https://cdn.test/a/b__thumb.jpg',
            ],
            's3 without a base'     => [['r2' => ['type' => 's3', 'default' => true]], '/a/b__thumb.jpg'],
        ];
    }

    #[Test]
    public function imageResizerUsesTheConfiguredBinary(): void
    {
        $resizer = (new ImageResizerFactory())($this->container([
            'backend' => ['local' => ['binary' => '/opt/magick']],
        ]));

        static::assertInstanceOf(ImageResizer::class, $resizer);
    }

    #[Test]
    public function profileProviderReadsStorageVariants(): void
    {
        $provider = (new ProfileProviderServiceFactory())($this->container([
            'variants' => ['admin-thumb' => ['width' => 180, 'height' => 180]],
        ]));

        static::assertSame(180, $provider->variant('admin-thumb')?->width);
    }

    /**
     * @mago-expect lint:no-literal-password A dummy shared secret.
     */
    #[Test]
    public function servicesAreBuiltFromTheirDependencies(): void
    {
        $profiles  = new ProfileProviderService([]);
        $urls      = new AssetUrlBuilder('');
        $manager   = new StorageManager();
        $resolver  = new OnDemandVariantResolver($manager);
        $generator = new VariantGenerator(new StubImageResizer(), $profiles, '/var/www');
        $container = new InMemoryContainer([
            'config'                       => ['storage' => ['backend' => ['local' => ['generate_secret' => 's']]]],
            ProfileProviderService::class  => $profiles,
            AssetUrlBuilder::class         => $urls,
            StorageManager::class          => $manager,
            OnDemandVariantResolver::class => $resolver,
            VariantGenerator::class        => $generator,
            ImageResizer::class            => new StubImageResizer(),
        ]);

        static::assertInstanceOf(VariantsCommand::class, (new VariantsCommandFactory())($container));
        static::assertInstanceOf(AssetVariantController::class, (new AssetVariantControllerFactory())($container));
        static::assertInstanceOf(
            AssetVariantGenerateController::class,
            (new AssetVariantGenerateControllerFactory())($container),
        );
        static::assertInstanceOf(OnDemandVariantResolver::class, (new OnDemandVariantResolverFactory())($container));
        static::assertInstanceOf(VariantGenerator::class, (new VariantGeneratorFactory())($container));
        static::assertInstanceOf(StorageSizes::class, (new StorageSizesFactory())($container));
        static::assertInstanceOf(StorageSources::class, (new StorageSourcesFactory())($container));
        static::assertInstanceOf(StorageSrcSet::class, (new StorageSrcSetFactory())($container));
        static::assertInstanceOf(StorageUrl::class, (new StorageUrlFactory())($container));
    }

    /**
     * @param array<string, mixed> $backend
     */
    #[Test]
    #[DataProvider('urlBuilderProvider')]
    public function urlBuilderFollowsThePrimaryBackendScheme(array $backend, string $expected): void
    {
        $builder = (new AssetUrlBuilderFactory())($this->container(['backend' => $backend]));

        static::assertSame($expected, $builder->variantUrl('a/b.jpg', 'thumb'));
    }

    /**
     * @param array<string, mixed> $storage
     */
    private function container(array $storage): InMemoryContainer
    {
        return new InMemoryContainer(['config' => ['storage' => $storage]]);
    }
}
