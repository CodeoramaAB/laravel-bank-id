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
     * An HTTP error response (4xx/5xx) is BankID's answer about the order, so it
     * maps to null. A ConnectException is deliberately not caught: a network
     * failure says nothing about the order, which may still be alive upstream.
     * Returning null would make BankIDService::collect() drop a valid
     * transaction on a blip, so the caller gets the exception and can retry.
     * (Upstream janjoo-group/laravel-bank-id returns null for both.)
     *
     * @throws ConnectException when BankID cannot be reached
     */
    protected function postAndMap(
        string $endpoint,
        array $payload,
        string $responseClass
    ): ?object {
        try {
            $response = $this->performRequest($endpoint, $payload);
        } catch (BadResponseException) {
            return null;
        }

        return $this->mapper->map(
            $this->unwrapResponse($response),
            $responseClass
        );
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
