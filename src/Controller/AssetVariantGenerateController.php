<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller;

use Contenir\Asset\Laminas\Mvc\Service\OnDemandVariantResolver;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;

use function hash_equals;
use function json_encode;

/**
 * Origin endpoint for the R2 edge miss-proxy: given a sibling variant key the
 * CDN/Worker failed to find, generate it back into the bucket and report its URL.
 *
 * Guarded by a shared secret (the `storage.asset.generate_secret`) sent in the
 * `X-Asset-Generate-Secret` header, so only the Worker can trigger generation.
 */
final class AssetVariantGenerateController extends AbstractActionController
{
    private const SECRET_HEADER = 'X-Asset-Generate-Secret';

    public function __construct(
        private OnDemandVariantResolver $resolver,
        private string $secret,
    ) {
    }

    public function generateAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        if ($this->secret === '') {
            return $this->json($response, 503, ['error' => 'generation endpoint not configured']);
        }

        $header   = $this->getRequest()->getHeaders()->get(self::SECRET_HEADER);
        $provided = $header === false ? '' : (string) $header->getFieldValue();
        if (! hash_equals($this->secret, $provided)) {
            return $response->setStatusCode(403);
        }

        $key = (string) $this->params()->fromQuery('key', '');
        if ($key === '') {
            return $this->json($response, 400, ['error' => 'missing key']);
        }

        $url = $this->resolver->generate($key);
        if ($url === null) {
            return $response->setStatusCode(404);
        }

        return $this->json($response, 200, ['url' => $url]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function json(Response $response, int $status, array $data): Response
    {
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->setContent((string) json_encode($data));

        return $response;
    }
}
