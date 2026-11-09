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

    /**
     * True while the order is pending and no app has picked it up yet: the QR
     * code has not been scanned and the app has not been opened.
     *
     * @see https://developers.bankid.com/api-references/auth--sign/collect
     */
    public function isAwaitingStart(): bool
    {
        return $this->status === Status::PENDING
            && in_array($this->hintCode, ['outstandingTransaction', 'noClient'], true);
    }

    /**
     * True when the order failed because it was not started within
     * BankIDTransaction::START_TIMEOUT_SECONDS (hintCode `startFailed`).
     */
    public function isStartTimeout(): bool
    {
        return $this->status === Status::FAILED && $this->hintCode === 'startFailed';
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
