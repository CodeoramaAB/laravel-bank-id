<?php

namespace Jgroup\BankID\Service\Models;

class CollectResult
{
    protected string $status;

    protected ?string $hintCode = null;

    protected ?string $qrCode = null;

    protected BankIDTransaction $transaction;

    protected ?CompletionResult $completionResult = null;

    public function __construct(BankIDTransaction $transaction, string $status, ?string $hintCode)
    {
        $this->transaction = $transaction;
        $this->status = Status::fromString($status);
        $this->hintCode = $hintCode;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(string $qrCode): void
    {
        $this->qrCode = $qrCode;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getHintCode(): ?string
    {
        return $this->hintCode;
    }

    public function getTransaction(): BankIDTransaction
    {
        return $this->transaction;
    }

    public function setTransaction(BankIDTransaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function getCompletionResult(): ?CompletionResult
    {
        return $this->completionResult;
    }

    public function setCompletionResult(CompletionResult $completionResult): void
    {
        $this->completionResult = $completionResult;
    }
}
