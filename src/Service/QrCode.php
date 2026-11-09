<?php

namespace Jgroup\BankID\Service;

/**
 * Animated BankID QR codes.
 *
 * @see https://developers.bankid.com/how-to-guides/qr-code
 */
class QrCode
{
    /**
     * Build the QR code content: bankid.{qrStartToken}.{time}.{qrAuthCode}, where
     * qrAuthCode = hex(HMAC-SHA256(key: qrStartSecret, data: time)).
     *
     * `time` is the number of whole seconds since the auth or sign response was
     * returned, measured on the backend. qrStartSecret must never be sent to the
     * frontend: compute the payload on the server and send only the result.
     */
    public static function payload(string $qrStartToken, string $qrStartSecret, int $time): string
    {
        $qrAuthCode = hash_hmac('sha256', (string) $time, $qrStartSecret);

        return "bankid.{$qrStartToken}.{$time}.{$qrAuthCode}";
    }
}
