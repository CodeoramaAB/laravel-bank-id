<?php

namespace Jgroup\BankID\RpApi;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Response as HttpResponse;
use Jgroup\BankID\RpApi\Models\AuthRequest;
use Jgroup\BankID\RpApi\Models\CancelRequest;
use Jgroup\BankID\RpApi\Models\CollectRequest;
use Jgroup\BankID\RpApi\Models\CollectResponse;
use Jgroup\BankID\RpApi\Models\SignRequest;
use Jgroup\BankID\RpApi\Models\StartTransactionResponse;
use JsonMapper;
use stdClass;

class RpApi
{
    protected ClientInterface $httpClient;

    protected JsonMapper $mapper;

    /**
     * Extra Guzzle options applied per request
     */
    protected array $additionalGuzzleOptions = [];

    public function __construct(ClientInterface $httpClient, JsonMapper $mapper)
    {
        $this->httpClient = $httpClient;
        $this->mapper = $mapper;
    }

    /* -----------------------------------------------------------------
     |  Public API
     | -----------------------------------------------------------------
     */

    public function auth(AuthRequest $request): ?StartTransactionResponse
    {
        return $this->postAndMap(
            'auth',
            $request->toArray(),
            StartTransactionResponse::class
        );
    }

    public function sign(SignRequest $request): ?StartTransactionResponse
    {
        return $this->postAndMap(
            'sign',
            $request->toArray(),
            StartTransactionResponse::class
        );
    }

    public function collect(CollectRequest $request): ?CollectResponse
    {
        return $this->postAndMap(
            'collect',
            $request->toArray(),
            CollectResponse::class
        );
    }

    public function cancel(CancelRequest $request): bool
    {
        try {
            $response = $this->performRequest('cancel', $request->toArray());
        } catch (BadResponseException|ConnectException) {
            return false;
        }

        return $response->getStatusCode() === HttpResponse::HTTP_OK;
    }

    /* -----------------------------------------------------------------
     |  Configuration
     | -----------------------------------------------------------------
     */

    public function setCertificatePaths(string $certPath, string $certKeyPath): void
    {
        if (! is_file($certPath)) {
            throw new \InvalidArgumentException("SSL certificate not found: {$certPath}");
        }

        if (! is_file($certKeyPath)) {
            throw new \InvalidArgumentException("SSL certificate key not found: {$certKeyPath}");
        }

        $this->additionalGuzzleOptions[RequestOptions::CERT] = $certPath;
        $this->additionalGuzzleOptions[RequestOptions::SSL_KEY] = $certKeyPath;
    }

    public function getAdditionalGuzzleOptions(): array
    {
        return $this->additionalGuzzleOptions;
    }

    /* -----------------------------------------------------------------
     |  Internals
     | -----------------------------------------------------------------
     */

    /**
     * A client error about the order (e.g. 400 invalidParameters, 404) is
     * BankID's answer, so it maps to null. A failure that says nothing about
     * the order is thrown instead: a ConnectException, 408 requestTimeout,
     * 429 and any 5xx (500 internalError, 503 maintenance). The order may still
     * be alive upstream. Returning null would make BankIDService::collect()
     * drop a valid transaction on a blip, so the caller gets the exception and
     * can retry. (Upstream janjoo-group/laravel-bank-id returns null for all.)
     *
     * @throws ConnectException when BankID cannot be reached
     * @throws BadResponseException on a transient HTTP error
     */
    protected function postAndMap(
        string $endpoint,
        array $payload,
        string $responseClass
    ): ?object {
        try {
            $response = $this->performRequest($endpoint, $payload);
        } catch (BadResponseException $e) {
            if ($this->isTransient($e)) {
                throw $e;
            }

            return null;
        }

        return $this->mapper->map(
            $this->unwrapResponse($response),
            $responseClass
        );
    }

    /**
     * Whether an HTTP error leaves the order's state unknown rather than
     * describing it.
     */
    protected function isTransient(BadResponseException $e): bool
    {
        $status = $e->getResponse()->getStatusCode();

        return $status >= 500 || in_array($status, [408, 429], true);
    }

    protected function performRequest(string $endpoint, array $payload): Response
    {
        return $this->httpClient->post($endpoint, [
            RequestOptions::JSON => $payload,
            ...$this->additionalGuzzleOptions,
        ]);
    }

    protected function unwrapResponse(Response $response): stdClass
    {
        $decoded = json_decode($response->getBody()->getContents());

        if (! $decoded instanceof stdClass) {
            throw new \RuntimeException('Invalid JSON response from BankID API');
        }

        return $decoded;
    }
}
