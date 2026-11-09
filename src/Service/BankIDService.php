<?php

namespace Jgroup\BankID\Service;

use Carbon\Carbon;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Contracts\Session\Session;
use Jgroup\BankID\RpApi\Models\AuthRequest;
use Jgroup\BankID\RpApi\Models\CancelRequest;
use Jgroup\BankID\RpApi\Models\CollectRequest;
use Jgroup\BankID\RpApi\Models\SignRequest;
use Jgroup\BankID\RpApi\RpApi;
use Jgroup\BankID\Service\Models\BankIDTransaction;
use Jgroup\BankID\Service\Models\CollectResult;
use Jgroup\BankID\Service\Models\CompletionResult;
use Jgroup\BankID\Service\Models\Http\CancelResponse;
use Jgroup\BankID\Service\Models\Http\CollectResponse;
use Jgroup\BankID\Service\Models\Http\TransactionResponse;
use Jgroup\BankID\Service\Models\Status;
use Jgroup\BankID\Signature\DigitalSignature;
use Psr\Log\LoggerInterface;

class BankIDService
{
    public const LOG_CHANNEL_COMPLETIONS = 'bankid-completions';

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
        $this->rpApi = $rpApi;
        $this->session = $session;
        $this->logger = $logger;
        $this->sessionKey = $sessionKey;
    }

    /* -----------------------------------------------------------------
     | Public API
     | -----------------------------------------------------------------
     */

    public function sign(
        string $endUserIp,
        string $userVisibleData,
        ?string $userVisibleDataFormat = null,
        ?string $userNonVisibleData = null,
        ?array $requirement = null
    ): ?TransactionResponse {
        $request = new SignRequest($endUserIp, $userVisibleData);
        if ($userVisibleDataFormat) {
            $request->setUserVisibleDataFormat($userVisibleDataFormat);
        }
        if ($userNonVisibleData) {
            $request->setUserNonVisibleData($userNonVisibleData);
        }

        return $this->startTransaction(fn () => $this->rpApi->sign($request), false);
    }

    public function auth(
        string $endUserIp,
        ?string $returnUrl = null,
        ?string $userVisibleData = null,
        ?string $userVisibleDataFormat = null,
        ?string $userNonVisibleData = null,
        ?array $requirement = null
    ): ?TransactionResponse {
        $request = new AuthRequest($endUserIp);
        if ($returnUrl) {
            $request->setReturnUrl($returnUrl);
        }
        if ($userVisibleData) {
            $request->setUserVisibleData($userVisibleData);
            if ($userVisibleDataFormat) {
                $request->setUserVisibleDataFormat($userVisibleDataFormat);
            }
        }
        if ($userNonVisibleData) {
            $request->setUserNonVisibleData($userNonVisibleData);
        }
        if ($requirement) {
            $request->setRequirement($requirement);
        }

        return $this->startTransaction(fn () => $this->rpApi->auth($request), true);
    }

    public function collect(): ?CollectResponse
    {
        $transaction = $this->getSessionTransaction();
        if (! $transaction) {
            return null;
        }

        // perform collect only if needed
        $collectResponse = $this->shouldCallBankIDCollect($transaction)
            ? $this->performCollect($transaction)
            : $transaction->getLastCollectResponse();

        if (! $collectResponse) {
            $this->setSessionTransaction(null);

            return null;
        }

        // build result DTO
        $collectResult = new CollectResult(
            $transaction,
            $collectResponse->getStatus(),
            $collectResponse->getHintCode()
        );

        // QR code if still pending
        if ($collectResult->getStatus() === Status::PENDING && $collectResult->getHintCode() === 'outstandingTransaction') {
            $collectResult->setQrCode($this->generateQrCode($transaction));
        }

        // attach completion result if done
        if ($collectResult->getStatus() === Status::COMPLETE) {
            $this->attachCompletionResult($collectResult, $collectResponse);
        }

        // persist updated transaction
        $this->setSessionTransaction($collectResult->getTransaction());

        return new CollectResponse(
            $collectResult->getTransaction()->getTransactionId(),
            $collectResult
        );
    }

    public function cancel(): CancelResponse
    {
        return new CancelResponse($this->cancelOngoingTransaction());
    }

    public function setCertificatePaths(string $certPath, string $certKeyPath): void
    {
        $this->rpApi->setCertificatePaths($certPath, $certKeyPath);
    }

    public function getSessionTransaction(): ?BankIDTransaction
    {
        return $this->session->get($this->sessionKey);
    }

    public function setSessionTransaction(?BankIDTransaction $transaction = null): void
    {
        $this->session->put($this->sessionKey, $transaction);
    }

    /* -----------------------------------------------------------------
     | Protected Helpers
     | -----------------------------------------------------------------
     */

    protected function startTransaction(callable $starter, bool $isAuth): ?TransactionResponse
    {
        $this->cancelPendingTransaction();

        $startTransaction = $starter();
        if (! $startTransaction) {
            $this->setSessionTransaction(null);

            return null;
        }

        $transaction = new BankIDTransaction(
            $startTransaction->getOrderRef(),
            $startTransaction->getQrStartToken(),
            $startTransaction->getQrStartSecret(),
            $startTransaction->getAutoStartToken()
        );
        $transaction->setIsAuthTransaction($isAuth);

        $this->setSessionTransaction($transaction);

        return new TransactionResponse(
            $transaction->getTransactionId(),
            $transaction->getAutoStartToken()
        );
    }

    protected function cancelPendingTransaction(): void
    {
        $transaction = $this->getSessionTransaction();
        if ($transaction && $transaction->getStatus() === Status::PENDING) {
            $this->cancelOngoingTransaction();
        }
    }

    protected function cancelOngoingTransaction(): bool
    {
        $transaction = $this->getSessionTransaction();
        if (! $transaction) {
            return false;
        }

        try {
            $this->rpApi->cancel(new CancelRequest($transaction->getOrderRef()));
            $this->setSessionTransaction(null);

            return true;
        } catch (BadResponseException) {
            return false;
        }
    }

    protected function shouldCallBankIDCollect(BankIDTransaction $transaction): bool
    {
        $lastCollect = $transaction->getLastCollect();

        return ! $transaction->getLastCollectResponse() || ! $lastCollect || $lastCollect->diffInSeconds(Carbon::now()) > 1;
    }

    protected function performCollect(BankIDTransaction $transaction)
    {
        $collectResponse = $this->rpApi->collect(new CollectRequest($transaction->getOrderRef()));
        if (! $collectResponse) {
            return null;
        }

        $transaction->setLastCollectResponse($collectResponse);
        $transaction->setLastCollect(Carbon::now());
        $transaction->setStatus(Status::fromString($collectResponse->getStatus()));

        if ($transaction->getStatus() === Status::COMPLETE) {
            $this->logger->channel(self::LOG_CHANNEL_COMPLETIONS)->info(json_encode($collectResponse));
        }

        return $collectResponse;
    }

    protected function attachCompletionResult(CollectResult $result, $collectResponse): void
    {
        $completion = $collectResponse->getCompletionData();
        $user = $completion->getUser();

        $signatureXml = base64_decode($completion->getSignature());
        $digSig = new DigitalSignature($signatureXml);

        $visibleData = $digSig->getUserVisibleData()
            ? base64_decode($digSig->getUserVisibleData())
            : null;

        $result->setCompletionResult(new CompletionResult(
            $user->getName(),
            $user->getPersonalNumber(),
            $visibleData
        ));
    }

    protected function generateQrCode(BankIDTransaction $transaction): string
    {
        return $transaction->getQrData();
    }
}
