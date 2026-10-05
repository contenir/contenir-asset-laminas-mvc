<?php

declare(strict_types=1);

namespace Contenir\Asset\Laminas\Mvc\Controller;

use Contenir\Asset\Laminas\Mvc\Service\OnDemandVariantResolver;
use Contenir\Storage\Exception\WriteException;
use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use SensitiveParameter;

use function hash_equals;
use function is_string;
use function json_encode;

/**
 * Origin endpoint for the R2 edge miss-proxy: given a sibling variant key the
 * CDN/Worker failed to find, generate it back into the bucket and report its URL.
 *
 * Guarded by a shared secret (the primary backend's `generate_secret`) sent in
 * the `X-Asset-Generate-Secret` header, so only the Worker can trigger generation.
 */
final class AssetVariantGenerateController extends AbstractActionController
{
    private const string SECRET_HEADER = 'X-Asset-Generate-Secret';

    public function __construct(
        private readonly OnDemandVariantResolver $resolver,
        #[SensitiveParameter]
        private readonly string $secret,
    ) {}

    /**
     * @param array<string, string> $data
     */
    private static function json(Response $response, int $status, array $data): Response
    {
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->setContent((string) json_encode($data));

        return $response;
    }

    /**
     * @throws WriteException If the owning backend cannot generate or store the variant.
     *
     * @mago-expect analysis:mixed-assignment Query parameters are untyped request input; checked here.
     */
    public function generateAction(): Response
    {
        $response = new Response();
        if ('' === $this->secret) {
            return self::json($response, Response::STATUS_CODE_503, ['error' => 'generation endpoint not configured']);
        }

        $request = $this->getRequest();
        if (! $request instanceof Request) {
            return $response->setStatusCode(Response::STATUS_CODE_400);
        }

        $header   = $request->getHeader(self::SECRET_HEADER);
        $provided = $header instanceof GenericHeader ? $header->getFieldValue() : '';
        if (! hash_equals($this->secret, $provided)) {
            return $response->setStatusCode(Response::STATUS_CODE_403);
        }

        $key = $request->getQuery('key', '');
        if (! is_string($key) || '' === $key) {
            return self::json($response, Response::STATUS_CODE_400, ['error' => 'missing key']);
        }

        $url = $this->resolver->generate($key);
        if (null === $url) {
            return $response->setStatusCode(Response::STATUS_CODE_404);
        }

        return self::json($response, Response::STATUS_CODE_200, ['url' => $url]);
    }
}
