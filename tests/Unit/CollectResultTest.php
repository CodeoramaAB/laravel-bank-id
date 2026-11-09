<?php

namespace Jgroup\BankID\Tests;

use Jgroup\BankID\Service\Models\BankIDTransaction;
use Jgroup\BankID\Service\Models\CollectResult;
use Jgroup\BankID\Service\Models\Status;

/**
 * Hint codes as documented for /collect (developers.bankid.com/api-references/auth--sign/collect).
 */
class CollectResultTest extends TestCase
{
    /**
     * @dataProvider orderStates
     */
    public function test_it_reports_whether_the_order_is_waiting_to_be_started(string $status, ?string $hintCode, bool $awaitingStart, bool $startTimeout)
    {
        $result = new CollectResult(
            new BankIDTransaction('order-ref', 'qr-start-token', 'qr-start-secret', 'auto-start-token'),
            $status,
            $hintCode
        );

        $this->assertSame($awaitingStart, $result->isAwaitingStart());
        $this->assertSame($startTimeout, $result->isStartTimeout());
    }

    public static function orderStates(): array
    {
        return [
            'not yet received by an app' => [Status::PENDING, 'outstandingTransaction', true, false],
            'app not launched yet' => [Status::PENDING, 'noClient', true, false],
            'app launched, no usable id yet' => [Status::PENDING, 'started', false, false],
            'QR scanned, user signing' => [Status::PENDING, 'userSign', false, false],
            'not started in time' => [Status::FAILED, 'startFailed', false, true],
            'cancelled by the user' => [Status::FAILED, 'userCancel', false, false],
            'complete' => [Status::COMPLETE, null, false, false],
        ];
    }
}
