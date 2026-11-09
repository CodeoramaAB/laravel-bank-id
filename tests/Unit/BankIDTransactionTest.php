<?php

namespace Jgroup\BankID\Tests;

use Carbon\Carbon;
use Jgroup\BankID\Service\Models\BankIDTransaction;

class BankIDTransactionTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_age_is_counted_in_whole_seconds()
    {
        // Carbon 3's diffInSeconds() returns a float, which used to leak into
        // the QR code as e.g. "bankid.TOKEN.1.8.HMAC" (review finding 1).
        Carbon::setTestNow('2026-11-09 10:00:00.700');
        $transaction = $this->makeTransaction();

        Carbon::setTestNow('2026-11-09 10:00:02.500');

        $this->assertSame(1, $transaction->getAgeInSeconds());
    }

    public function test_the_age_is_measured_against_a_given_time()
    {
        Carbon::setTestNow('2026-11-09 10:00:00');
        $transaction = $this->makeTransaction();

        $this->assertSame(25, $transaction->getAgeInSeconds(Carbon::parse('2026-11-09 10:00:25')));
    }

    public function test_the_age_is_never_negative()
    {
        Carbon::setTestNow('2026-11-09 10:00:10');
        $transaction = $this->makeTransaction();

        $this->assertSame(0, $transaction->getAgeInSeconds(Carbon::parse('2026-11-09 10:00:05')));
    }

    public function test_an_order_must_be_started_within_thirty_seconds()
    {
        $this->assertSame(30, BankIDTransaction::START_TIMEOUT_SECONDS);
    }

    protected function makeTransaction(): BankIDTransaction
    {
        return new BankIDTransaction('order-ref', 'qr-start-token', 'qr-start-secret', 'auto-start-token');
    }
}
