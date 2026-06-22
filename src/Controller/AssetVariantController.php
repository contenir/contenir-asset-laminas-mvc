<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller;

use Contenir\Asset\Laminas\Mvc\Service\VariantGenerator;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;

use function filesize;
use function finfo_close;
use function finfo_file;
use function finfo_open;
use function header;
use function is_file;
use function readfile;
use function urldecode;

use const FILEINFO_MIME_TYPE;

/**
 * Serves keyed image variants on demand under
 * `/asset/<folder>/_variant/<name>/<filename>`.
 *
 * Existing variant files are served directly by the web server; only missing
 * ones reach this controller, which materialises them via {@see VariantGenerator}
 * and streams the result (or 404s when it cannot be produced).
 */
final class AssetVariantController extends AbstractActionController
{
    public function __construct(private VariantGenerator $generator)
    {
    }

    public function indexAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();
        $folder   = urldecode((string) $this->params('folder'));
        $name     = (string) $this->params('name');
        $filename = urldecode((string) $this->params('filename'));

        $path = $this->generator->generate($folder, $name, $filename);
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
