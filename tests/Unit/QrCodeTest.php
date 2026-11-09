<?php

namespace Jgroup\BankID\Tests;

use Carbon\Carbon;
use Jgroup\BankID\Service\Models\BankIDTransaction;
use Jgroup\BankID\Service\QrCode;

/**
 * Animated QR codes, checked against the reference values in BankID's QR guide
 * (developers.bankid.com/how-to-guides/qr-code, "Example").
 */
class QrCodeTest extends TestCase
{
    const QR_START_TOKEN = '67df3917-fa0d-44e5-b327-edcc928297f8';

    const QR_START_SECRET = 'd28db9a7-4cde-429e-a983-359be676944c';

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @dataProvider bankIdReferenceValues
     */
    public function test_the_payload_matches_bankids_reference_values(int $time, string $qrAuthCode)
    {
        $this->assertSame(
            'bankid.'.self::QR_START_TOKEN.'.'.$time.'.'.$qrAuthCode,
            QrCode::payload(self::QR_START_TOKEN, self::QR_START_SECRET, $time)
        );
    }

    public static function bankIdReferenceValues(): array
    {
        return [
            't=0' => [0, 'dc69358e712458a66a7525beef148ae8526b1c71610eff2c16cdffb4cdac9bf8'],
            't=1' => [1, '949d559bf23403952a94d103e67743126381eda00f0b3cbddbf7c96b1adcbce2'],
            't=2' => [2, 'a9e5ec59cb4eee4ef4117150abc58fad7a85439a6a96ccbecc3668b41795b3f3'],
        ];
    }

    public function test_a_transaction_builds_the_qr_code_for_the_current_second()
    {
        Carbon::setTestNow('2026-11-09 10:00:00');
        $transaction = new BankIDTransaction('order-ref', self::QR_START_TOKEN, self::QR_START_SECRET, 'auto-start');

        Carbon::setTestNow('2026-11-09 10:00:02');

        $this->assertSame(
            QrCode::payload(self::QR_START_TOKEN, self::QR_START_SECRET, 2),
            $transaction->getQrData()
        );
    }
}
