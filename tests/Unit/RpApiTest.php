<?php

namespace Jgroup\BankID\Tests;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Jgroup\BankID\RpApi\Models\AuthRequest;
use Jgroup\BankID\RpApi\Models\CancelRequest;
use Jgroup\BankID\RpApi\Models\CollectRequest;
use Jgroup\BankID\RpApi\Models\CollectResponse;
use Jgroup\BankID\RpApi\Models\SignRequest;
use Jgroup\BankID\RpApi\Models\StartTransactionResponse;
use Jgroup\BankID\RpApi\RpApi;

class RpApiTest extends TestCase
{
    const CLIENT_IP = '127.0.0.1';

    protected $rpApi;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'bankid.use_environment' => 'test',
        ]);

        $this->rpApi = $this->app->make(RpApi::class);
    }

    public function test_it_checks_that_the_bankid_rp_api_connection_works()
    {
        $this->assertNotNull(
            $this->rpApi->auth(new AuthRequest(self::CLIENT_IP))
        );
    }

    public function test_it_makes_one_auth_cycle()
    {
        $startTransaction = $this->rpApi->auth(new AuthRequest(self::CLIENT_IP));

        $this->assertInstanceOf(StartTransactionResponse::class, $startTransaction);
        $this->assertNotNull($startTransaction->getOrderRef());
        $this->assertNotNull($startTransaction->getAutoStartToken());
        $this->assertNotNull($startTransaction->getQrStartToken());
        $this->assertNotNull($startTransaction->getQrStartSecret());

        // Collect
        $collectResponse = $this->rpApi->collect(new CollectRequest($startTransaction->getOrderRef()));
        $this->assertNotNull($collectResponse);
        $this->assertInstanceOf(CollectResponse::class, $collectResponse);

        $this->assertNotNull($collectResponse->getOrderRef());
        $this->assertEquals($startTransaction->getOrderRef(), $collectResponse->getOrderRef());

        $this->assertNotNull($collectResponse->getStatus());
        $this->assertEquals('pending', $collectResponse->getStatus());
        $this->assertNotNull($collectResponse->getHintCode());
        $this->assertEquals('outstandingTransaction', $collectResponse->getHintCode());

        // Cancel
        $cancelResponse = $this->rpApi->cancel(new CancelRequest($startTransaction->getOrderRef()));
        $this->assertTrue($cancelResponse);
    }

    public function test_it_makes_one_sign_cycle()
    {
        $startTransaction = $this->rpApi->sign(new SignRequest(self::CLIENT_IP, 'visible'));

        $this->assertInstanceOf(StartTransactionResponse::class, $startTransaction);
        $this->assertNotNull($startTransaction->getOrderRef());
        $this->assertNotNull($startTransaction->getAutoStartToken());
        $this->assertNotNull($startTransaction->getQrStartToken());
        $this->assertNotNull($startTransaction->getQrStartSecret());

        // Collect
        $collectResponse = $this->rpApi->collect(new CollectRequest($startTransaction->getOrderRef()));
        $this->assertNotNull($collectResponse);
        $this->assertInstanceOf(CollectResponse::class, $collectResponse);

        $this->assertNotNull($collectResponse->getOrderRef());
        $this->assertEquals($startTransaction->getOrderRef(), $collectResponse->getOrderRef());

        $this->assertNotNull($collectResponse->getStatus());
        $this->assertEquals('pending', $collectResponse->getStatus());
        $this->assertNotNull($collectResponse->getHintCode());
        $this->assertEquals('outstandingTransaction', $collectResponse->getHintCode());

        // Cancel
        $cancelResponse = $this->rpApi->cancel(new CancelRequest($startTransaction->getOrderRef()));
        $this->assertTrue($cancelResponse);
    }

    public function test_it_handles_certificate_paths_correctly()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SSL certificate not found: /nonexistent/cert.pem');

        $this->rpApi->setCertificatePaths('/nonexistent/cert.pem', '/nonexistent/key.pem');

        $this->rpApi->auth(new AuthRequest(self::CLIENT_IP));
    }

    public function test_it_passes_cert_paths_to_guzzle()
    {
        // create temporary valid PEM files (even empty cert/key works for testing)
        $certFile = tempnam(sys_get_temp_dir(), 'cert').'.pem';
        $keyFile = tempnam(sys_get_temp_dir(), 'key').'.pem';

        // minimal valid PEM content
        file_put_contents($certFile, "-----BEGIN CERTIFICATE-----\nMIIB...fake...\n-----END CERTIFICATE-----");
        file_put_contents($keyFile, "-----BEGIN PRIVATE KEY-----\nMIIE...fake...\n-----END PRIVATE KEY-----");

        $container = [];
        $history = Middleware::history($container);
        $handlerStack = HandlerStack::create();
        $handlerStack->push($history);

        $this->rpApi = $this->app->make(RpApi::class, ['handler' => $handlerStack]);
        $this->rpApi->setCertificatePaths($certFile, $keyFile);

        try {
            $this->rpApi->auth(new AuthRequest(self::CLIENT_IP));
        } catch (\Exception $e) {
            // ignore exceptions from the BankID test server
        }

        $this->assertNotEmpty($container, 'Guzzle request was sent');

        $options = $container[0]['options'] ?? [];
        $this->assertEquals($certFile, $options['cert'] ?? null);
        $this->assertEquals($keyFile, $options['ssl_key'] ?? null);
    }
}
