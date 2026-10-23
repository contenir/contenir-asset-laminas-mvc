<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller;

use Contenir\Asset\Exception\ExceptionInterface;
use Contenir\Asset\Image\LegacyResizer;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;

use function date;
use function file_get_contents;
use function is_string;
use function mime_content_type;
use function strlen;
use function urldecode;

/**
 * The `imageresize` route's controller: serves the legacy dot-notation
 * `.<dimensions>` scheme (`/asset/<folder>/.480x/<file>`) that
 * contenir-asset's `assetSrcSet()` emits, through its `LegacyResizer`. A
 * web server serves already generated files directly; this action only
 * runs on a miss.
 *
 * @api
 *
 * @deprecated Superseded by {@see AssetVariantController}, which serves keyed
 *             variants (incl. WebP/AVIF) at `/asset/<folder>/_variant/<name>/<file>`.
 *             Kept working for sites still on the `.<dimensions>` scheme.
 */
final class ImageResizeController extends AbstractActionController
{
    public function __construct(
        private readonly LegacyResizer $resizer,
    ) {}

    private static function file(string $path): Response
    {
        $content  = (string) file_get_contents($path);
        $mimeType = mime_content_type($path);
        $response = self::respond(Response::STATUS_CODE_200, $content);
        $response->getHeaders()
            ->addHeaders([
                'Content-Type'         => false === $mimeType ? 'application/octet-stream' : $mimeType,
                'Content-Length'       => (string) strlen($content),
                'X-Contenir-Generated' => date('r'),
            ]);

        return $response;
    }

    private static function param(mixed $value): string
    {
        return is_string($value) ? urldecode($value) : '';
    }

    private static function respond(int $status, string $content): Response
    {
        $response = new Response();
        $response->setStatusCode($status);
        $response->setContent($content);

        return $response;
    }

    public function resizeAction(): Response
    {
        if (! $this->resizer->isConfigured()) {
            return self::respond(Response::STATUS_CODE_501, 'Image resizing is not configured');
        }

        $match = $this->getEvent()->getRouteMatch();

        try {
            $path = $this->resizer->resize(
                self::param($match?->getParam('folder')),
                self::param($match?->getParam('dimensions')),
                self::param($match?->getParam('filename')),
            );
        } catch (ExceptionInterface $e) {
            return self::respond(Response::STATUS_CODE_500, $e->getMessage());
        }

        return null === $path ? self::respond(Response::STATUS_CODE_404, 'File not found') : self::file($path);
    }
}
