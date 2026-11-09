<?php

namespace Jgroup\BankID\RpApi\Models;

class StartTransactionResponse
{
    protected ?string $autoStartToken = null;

    protected ?string $orderRef = null;

    protected ?string $qrStartToken = null;

    protected ?string $qrStartSecret = null;

    public function getAutoStartToken(): ?string
    {
        return $this->autoStartToken;
    }

    public function setAutoStartToken(string $autoStartToken): void
    {
        $this->autoStartToken = $autoStartToken;
    }

    public function getOrderRef(): ?string
    {
        return $this->orderRef;
    }

    public function setOrderRef(string $orderRef): void
    {
        $this->orderRef = $orderRef;
    }

    public function getQrStartSecret(): ?string
    {
        return $this->qrStartSecret;
    }

    public function setQrStartSecret(string $qrStartSecret): void
    {
        $this->qrStartSecret = $qrStartSecret;
    }

    public function getQrStartToken(): ?string
    {
        return $this->qrStartToken;
    }

    public function setQrStartToken(string $qrStartToken): void
    {
        $this->qrStartToken = $qrStartToken;
    }
}
