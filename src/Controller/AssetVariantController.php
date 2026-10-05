<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller;

use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Override;

use function file_get_contents;
use function is_file;
use function is_string;
use function mime_content_type;
use function strlen;
use function urldecode;

/**
 * Serves keyed image variants on demand under
 * `/asset/<folder>/_variant/<name>/<filename>`.
 *
 * Existing variant files are served directly by the web server; only missing
 * ones reach this controller, which materialises them via {@see VariantGenerator}
 * and returns the bytes (or a 404 when they cannot be produced).
 */
final class AssetVariantController extends AbstractActionController
{
    public function __construct(
        private readonly VariantGenerator $generator,
    ) {}

    /**
     * @mago-expect analysis:docblock-type-mismatch The parent documents a ViewModel; this action answers with the bytes.
     * @mago-expect analysis:invalid-return-statement The parent documents a ViewModel; this action answers with the bytes.
     */
    #[Override]
    public function indexAction(): Response
    {
        $response = new Response();
        $path     = $this->generator->generate(
            urldecode($this->routeParam('folder')),
            $this->routeParam('name'),
            urldecode($this->routeParam('filename')),
        );

        if (null === $path || ! is_file($path)) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        $content = (string) file_get_contents($path);

        $mime = mime_content_type($path);
        $response->setContent($content);
        $response->getHeaders()
            ->addHeaders([
                'Content-Type'   => false === $mime ? 'application/octet-stream' : $mime,
                'Content-Length' => (string) strlen($content),
                'Cache-Control'  => 'public, max-age=31536000',
            ]);

        return $response;
    }

    /**
     * @mago-expect analysis:mixed-assignment Route parameters are untyped; checked here.
     */
    private function routeParam(string $name): string
    {
        $value = $this->getEvent()->getRouteMatch()?->getParam($name);

        return is_string($value) ? $value : '';
    }
}
