<?php

namespace Jgroup\BankID\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Jgroup\BankID\RpApi\Models\AuthRequest;
use Jgroup\BankID\RpApi\Models\CancelRequest;
use Jgroup\BankID\RpApi\Models\CollectRequest;
use Jgroup\BankID\RpApi\RpApi;
use JsonMapper;
use Psr\Http\Message\ResponseInterface;

/**
 * How RpApi reports the two kinds of failure.
 *
 * An HTTP error response from BankID (4xx/5xx) is an answer about the order,
 * so the endpoint returns null. A connection failure says nothing about the
 * order: it may still be alive upstream. It is therefore thrown, so the caller
 * can retry instead of treating the order as dead. Upstream returns null for
 * both, which made BankIDService::collect() drop a still-valid transaction on
 * a network blip.
 */
class RpApiErrorHandlingTest extends TestCase
{
    public function test_a_connection_failure_during_auth_is_thrown()
    {
        $rpApi = $this->rpApiRespondingWith(
            new ConnectException('Connection refused', new Request('POST', 'auth'))
        );

        $this->expectException(ConnectException::class);

        $rpApi->auth(new AuthRequest('127.0.0.1'));
    }

    public function test_a_connection_failure_during_collect_is_thrown()
    {
        $rpApi = $this->rpApiRespondingWith(
            new ConnectException('Connection reset', new Request('POST', 'collect'))
        );

        $this->expectException(ConnectException::class);

        $rpApi->collect(new CollectRequest('order-ref'));
    }

    public function test_an_http_error_response_returns_null()
    {
        $rpApi = $this->rpApiRespondingWith(
            new Response(400, ['Content-Type' => 'application/json'], '{"errorCode":"invalidParameters","details":"No such order"}')
        );

        $this->assertNull($rpApi->collect(new CollectRequest('order-ref')));
    }

    public function test_cancel_stays_best_effort_on_a_connection_failure()
    {
        $rpApi = $this->rpApiRespondingWith(
            new ConnectException('Connection refused', new Request('POST', 'cancel'))
        );

        $this->assertFalse($rpApi->cancel(new CancelRequest('order-ref')));
    }

    /**
     * @param  \Throwable|ResponseInterface  $response
     */
    protected function rpApiRespondingWith($response): RpApi
    {
        $client = new Client([
            'base_uri' => 'https://appapi2.test.bankid.com/rp/v6.0/',
            'handler' => HandlerStack::create(new MockHandler([$response])),
        ]);

        return new RpApi($client, new JsonMapper);
    }
}
