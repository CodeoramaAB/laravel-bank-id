<?php

namespace Jgroup\BankID\Service;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Jgroup\BankID\RpApi\RpApi;
use Jgroup\BankID\Service\Models\Status;
use Illuminate\Contracts\Session\Session;
use Jgroup\BankID\RpApi\Models\AuthRequest;
use Jgroup\BankID\RpApi\Models\SignRequest;
use Jgroup\BankID\RpApi\Models\CancelRequest;
use Jgroup\BankID\Signature\DigitalSignature;
use GuzzleHttp\Exception\BadResponseException;
use Jgroup\BankID\RpApi\Models\CollectRequest;
use Jgroup\BankID\Service\Models\CollectResult;
use Jgroup\BankID\Service\Models\CompletionResult;
use Jgroup\BankID\Service\Models\BankIDTransaction;
use Jgroup\BankID\Service\Models\Http\CancelResponse;
use Jgroup\BankID\Service\Models\Http\CollectResponse;
use Jgroup\BankID\Service\Models\Http\TransactionResponse;

class BankIDService
{
    const LOG_CHANNEL_COMPLETIONS = 'bankid-completions';

    protected RpApi $rpApi;

    protected Session $session;

    protected LoggerInterface $logger;

    protected string $sessionKey;

    public function __construct(
        RpApi $rpApi,
        Session $session,
        LoggerInterface $logger,
        string $sessionKey = 'bankid_transaction'
    ) {
        $this->rpApi      = $rpApi;
        $this->session    = $session;
        $this->logger     = $logger;
        $this->sessionKey = $sessionKey;
    }

    public function sign(
        string $endUserIp,
        string $userVisibleData,
        string $userVisibleDataFormat = null,
        string $userNonVisibleData = null,
        array $requirement = null
    ): ?TransactionResponse {
        if (
            $this->getSessionTransaction() &&
            $this->getSessionTransaction()->getStatus() === Status::PENDING
        ) {
            $this->cancelOngoingTransaction();
        }

        $signRequest = new SignRequest($endUserIp, $userVisibleData);

        if ($userVisibleDataFormat) {
            $signRequest->setUserVisibleDataFormat($userVisibleDataFormat);
        }

        if ($userNonVisibleData) {
            $signRequest->setUserNonVisibleData($userNonVisibleData);
        }

        $startTransaction = $this->rpApi->sign($signRequest);

        if (!$startTransaction) {
            $this->setSessionTransaction(null);
            return null;
        }

        $transaction = new BankIDTransaction(
            $startTransaction->getOrderRef(),
            $startTransaction->getQrStartToken(),
            $startTransaction->getQrStartSecret(),
            $startTransaction->getAutoStartToken()
        );

        $transaction->setIsAuthTransaction(false);

        $this->setSessionTransaction($transaction);

        return new TransactionResponse(
            $transaction->getTransactionId(),
            $transaction->getAutoStartToken()
        );
    }

    public function auth(
        string $endUserIp,
        string $returnUrl = null,
        string $userVisibleData = null,
        string $userVisibleDataFormat = null,
        string $userNonVisibleData = null,
        array $requirement = null
    ): ?TransactionResponse {
        if (
            $this->getSessionTransaction() &&
            $this->getSessionTransaction()->getStatus() === Status::PENDING
        ) {
            $this->cancelOngoingTransaction();
        }

        $authRequest = new AuthRequest($endUserIp);

        if ($returnUrl) {
            $authRequest->setReturnUrl($returnUrl);
        }

        if ($userVisibleData) {
            $authRequest->setUserVisibleData($userVisibleData);

            if ($userVisibleDataFormat) {
                $authRequest->setUserVisibleDataFormat($userVisibleDataFormat);
            }
        }

        if ($userNonVisibleData) {
            $authRequest->setUserNonVisibleData($userNonVisibleData);
        }

        if ($requirement) {
            $authRequest->setRequirement($requirement);
        }

        $startTransaction = $this->rpApi->auth($authRequest);

        if (!$startTransaction) {
            $this->setSessionTransaction(null);
            return null;
        }

        $transaction = new BankIDTransaction(
            $startTransaction->getOrderRef(),
            $startTransaction->getQrStartToken(),
            $startTransaction->getQrStartSecret(),
            $startTransaction->getAutoStartToken()
        );

        $transaction->setIsAuthTransaction(true);

        $this->setSessionTransaction($transaction);

        return new TransactionResponse(
            $transaction->getTransactionId(),
            $transaction->getAutoStartToken()
        );
    }

    public function collect(): ?CollectResponse
    {
        $transaction = $this->getSessionTransaction();

        if (!$transaction) {
            return null;
        }

        if ($this->shouldCallBankIDCollect($transaction)) {
            $collectResponse = $this->rpApi->collect(
                new CollectRequest($transaction->getOrderRef())
            );

            if (!$collectResponse) {
                $this->setSessionTransaction(null);
                return null;
            }

            $status = Status::fromString($collectResponse->getStatus());

            if ($status === Status::COMPLETE) {
                $this->logger
                    ->channel(self::LOG_CHANNEL_COMPLETIONS)
                    ->info(json_encode($collectResponse));
            }

            $transaction->setLastCollectResponse($collectResponse);
            $transaction->setLastCollect(Carbon::now());
            $transaction->setStatus($status);
        } else {
            $collectResponse = $transaction->getLastCollectResponse();
        }

        $collectResult = new CollectResult(
            $transaction,
            $collectResponse->getStatus(),
            $collectResponse->getHintCode(),
        );

        $this->setSessionTransaction($collectResult->getTransaction());

        if (
            $collectResult->getStatus() === Status::PENDING &&
            $collectResult->getHintCode() === 'outstandingTransaction'
        ) {
            $collectResult->setQrCode($this->getQRData($transaction));
        }

        if ($collectResult->getStatus() === Status::COMPLETE) {
            $name           = $collectResponse->getCompletionData()->getUser()->getName();
            $personalNumber = $collectResponse->getCompletionData()->getUser()->getPersonalNumber();

            $signatureXml = base64_decode($collectResponse->getCompletionData()->getSignature());

            $digSig = new DigitalSignature($signatureXml);

            $visibleData = null;
            if ($digSig->getUserVisibleData() != null && strlen($digSig->getUserVisibleData()) > 0) {
                $visibleData = base64_decode($digSig->getUserVisibleData());
            }

            $collectResult->setCompletionResult(
                new CompletionResult($name, $personalNumber, $visibleData)
            );
        }

        return new CollectResponse(
            $collectResult->getTransaction()->getTransactionId(),
            $collectResult
        );
    }

    public function cancel(): CancelResponse
    {
        return new CancelResponse($this->cancelOngoingTransaction());
    }

    protected function cancelOngoingTransaction(): bool
    {
        $transaction = $this->getSessionTransaction();

        if (!$transaction) {
            return false;
        }

        $orderRef = $transaction->getOrderRef();

        try {
            $this->rpApi->cancel(new CancelRequest($orderRef));

            $this->setSessionTransaction(null);

            return true;
        } catch (BadResponseException $e) {
            return false;
        }
    }

    public function getSessionTransaction(): ?BankIDTransaction
    {
        return $this->session->get($this->sessionKey);
    }

    public function setSessionTransaction(BankIDTransaction $transaction = null): void
    {
        $this->session->put($this->sessionKey, $transaction);
    }

    protected function getQRData(BankIDTransaction $transaction): string
    {
        $secondsElapsedSinceStartTime = $transaction->getStartTime()->diffInSeconds(Carbon::now());

        $qrAuthCode = hash_hmac(
            'sha256', $secondsElapsedSinceStartTime, $transaction->getQrStartSecret()
        );

        $qrStartToken = $transaction->getQrStartToken();

        return "bankid.{$qrStartToken}.{$secondsElapsedSinceStartTime}.{$qrAuthCode}";
    }

    protected function shouldCallBankIDCollect(BankIDTransaction $transaction): bool
    {
        if (!$transaction->getLastCollectResponse() || !$transaction->getLastCollect()) {
            return true;
        }

        $secondsElapsedSinceLastCollect = $transaction->getLastCollect()->diffInSeconds(Carbon::now());

        return $secondsElapsedSinceLastCollect > 1;
    }
}
