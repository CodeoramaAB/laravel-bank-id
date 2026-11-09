<?php

namespace Jgroup\BankID\Service\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Jgroup\BankID\RpApi\Models\CollectResponse;

/**
 * The BankID transaction stored in session.
 */
class BankIDTransaction
{
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
