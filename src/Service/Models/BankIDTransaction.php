<?php

namespace Jgroup\BankID\Service\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Jgroup\BankID\RpApi\Models\CollectResponse;
use Jgroup\BankID\Service\QrCode;

/**
 * The BankID transaction stored in session.
 */
class BankIDTransaction
{
    /**
     * How long the user has to start the order (scan the QR code or open the
     * app) after the auth or sign response. After that, collect reports
     * `failed` with hintCode `startFailed`, and a new order is needed.
     *
     * @see https://developers.bankid.com/how-to-guides/qr-code
     */
    public const START_TIMEOUT_SECONDS = 30;

    protected ?string $orderRef = null;

    protected ?string $qrStartToken = null;

    protected ?string $qrStartSecret = null;

    protected ?string $autoStartToken = null;

    protected string $transactionId;

    protected Carbon $startTime;

    protected string $status;

    protected ?Carbon $lastCollect = null;

    protected ?CollectResponse $lastCollectResponse = null;

    protected bool $isAuthTransaction = false;

    public function __construct(?string $orderRef, ?string $qrStartToken, ?string $qrStartSecret, ?string $autoStartToken)
    {
        $this->orderRef = $orderRef;
        $this->qrStartToken = $qrStartToken;
        $this->qrStartSecret = $qrStartSecret;
        $this->autoStartToken = $autoStartToken;
        $this->transactionId = (string) Str::uuid();
        $this->startTime = Carbon::now();
        $this->status = Status::PENDING;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getOrderRef(): string
    {
        return $this->orderRef;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStartTime(): Carbon
    {
        return $this->startTime;
    }

    /**
     * Whole seconds since the auth or sign response was returned, rounded down
     * and never negative.
     *
     * Millisecond timestamps are compared on purpose. Carbon 3's
     * diffInSeconds() returns a float, which used to leak into the QR code.
     */
    public function getAgeInSeconds(?Carbon $now = null): int
    {
        $now = $now ?? Carbon::now();
        $elapsedMs = (int) $now->getTimestampMs() - (int) $this->startTime->getTimestampMs();

        return intdiv(max(0, $elapsedMs), 1000);
    }

    /**
     * The animated QR code content for this order at the given moment.
     */
    public function getQrData(?Carbon $now = null): string
    {
        return QrCode::payload($this->getQrStartToken(), $this->getQrStartSecret(), $this->getAgeInSeconds($now));
    }

    public function getQrStartSecret(): string
    {
        return $this->qrStartSecret;
    }

    public function getQrStartToken(): string
    {
        return $this->qrStartToken;
    }

    public function getAutoStartToken(): string
    {
        return $this->autoStartToken;
    }

    public function setLastCollectResponse(?CollectResponse $collectResponse): void
    {
        $this->lastCollectResponse = $collectResponse;
    }

    public function getLastCollectResponse(): ?CollectResponse
    {
        return $this->lastCollectResponse;
    }

    public function setLastCollect(?Carbon $lastCollect): void
    {
        $this->lastCollect = $lastCollect;
    }

    public function getLastCollect(): ?Carbon
    {
        return $this->lastCollect;
    }

    public function setIsAuthTransaction(bool $isAuthTransaction): void
    {
        $this->isAuthTransaction = $isAuthTransaction;
    }

    public function getIsAuthTransaction(): bool
    {
        return $this->isAuthTransaction;
    }
}
