<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Tests\Unit\Controller;

use Contenir\Asset\Laminas\Mvc\Controller\AssetVariantGenerateController;
use Contenir\Asset\Laminas\Mvc\Service\OnDemandVariantResolver;
use Contenir\Asset\Laminas\Mvc\Tests\TestAsset\FakeOnDemandStorage;
use Contenir\Storage\StorageManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\Request as GenericRequest;
use Laminas\Stdlib\RequestInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SensitiveParameter;

#[Group('unit')]
final class AssetVariantGenerateControllerTest extends TestCase
{
    #[Test]
    public function answersNotFoundWhenNoBackendCanGenerate(): void
    {
        $response = $this->dispatch('s3cret', $this->request('s3cret', 'a__thumb.jpg'), new StorageManager());

        static::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function isUnavailableWithoutAConfiguredSecret(): void
    {
        $controller = new AssetVariantGenerateController(new OnDemandVariantResolver(new StorageManager()), '');

        $response = $controller->generateAction();

        static::assertSame([503, '{"error":"generation endpoint not configured"}'], [
            $response->getStatusCode(),
            $response->getContent(),
        ]);
    }

    #[Test]
    public function rejectsAMissingKey(): void
    {
        $response = $this->dispatch('s3cret', $this->request('s3cret', ''));

        static::assertSame([400, '{"error":"missing key"}'], [$response->getStatusCode(), $response->getContent()]);
    }

    #[Test]
    public function rejectsAMissingSecret(): void
    {
        static::assertSame(403, $this->dispatch('s3cret', new Request())->getStatusCode());
    }

    #[Test]
    public function rejectsANonHttpRequest(): void
    {
        static::assertSame(400, $this->dispatch('s3cret', new GenericRequest())->getStatusCode());
    }

    #[Test]
    public function rejectsAWrongSecret(): void
    {
        static::assertSame(403, $this->dispatch('s3cret', $this->request('wrong', 'k.jpg'))->getStatusCode());
    }

    #[Test]
    public function reportsTheUrlOfTheGeneratedVariant(): void
    {
        $response = $this->dispatch('s3cret', $this->request('s3cret', 'a__thumb.jpg'));

        static::assertSame(200, $response->getStatusCode());
        static::assertSame('{"url":"https:\/\/cdn.test\/a__thumb.jpg"}', $response->getContent());
        static::assertSame('application/json', $response->getHeaders()->get('Content-Type')?->getFieldValue());
    }

    private function dispatch(
        #[SensitiveParameter]
        string $secret,
        RequestInterface $request,
        ?StorageManager $manager = null,
    ): Response {
        if (null === $manager) {
            $manager = new StorageManager();
            $manager->register('r2', new FakeOnDemandStorage([]));
        }

        $controller = new AssetVariantGenerateController(new OnDemandVariantResolver($manager), $secret);
        $event      = new MvcEvent();
        $event->setRouteMatch(new RouteMatch(['action' => 'generate']));
        $controller->setEvent($event);

        $result = $controller->dispatch($request);
        static::assertInstanceOf(Response::class, $result);

        return $result;
    }

    private function request(#[SensitiveParameter] string $secret, string $key): Request
    {
        $request = new Request();
        $request->getHeaders()->addHeaderLine('X-Asset-Generate-Secret', $secret);
        $request->setQuery(new Parameters(['key' => $key]));

        return $request;
    }
}
