<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller;

use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;

use function finfo_close;
use function finfo_file;
use function finfo_open;

use const FILEINFO_MIME_TYPE;

/**
 * Serves responsive image variants on demand under
 * `/asset/<folder>/_variant/<dimensions>/<filename>`.
 *
 * Existing variant files are served directly by the web server; only missing ones
 * reach this controller. Resolution and on-demand generation live in
 * {@see VariantGenerator}; this controller streams whatever file the generator
 * produces (or 404s when it cannot produce one).
 */
final class AssetVariantController extends AbstractActionController
{
    public function __construct(private VariantGenerator $generator)
    {
    }

    public function indexAction(): Response
    {
        /** @var Response $response */
        $response   = $this->getResponse();
        $folder     = urldecode((string) $this->params('folder'));
        $dimensions = (string) $this->params('dimensions');
        $filename   = urldecode((string) $this->params('filename'));

        $path = $this->generator->generate($folder, $dimensions, $filename);
        if ($path === null || ! is_file($path)) {
            return $response->setStatusCode(404);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = ($finfo ? finfo_file($finfo, $path) : false) ?: 'application/octet-stream';
        if ($finfo) {
            finfo_close($finfo);
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: public, max-age=31536000');
        readfile($path);

        exit;
    }
}
