<?php

namespace Jgroup\BankID\Rules;

use Illuminate\Contracts\Validation\Rule;
use Jgroup\BankID\Facades\BankID;
use Jgroup\BankID\Service\Models\Status;
use Jgroup\BankID\Signature\DigitalSignature;

class SignCompleted implements Rule
{
    protected string $userVisibleData;

    public function __construct(string $userVisibleData)
    {
        $this->userVisibleData = $userVisibleData;
    }

    public function passes($attribute, $value)
    {
        $transaction = BankID::getSessionTransaction();

        if (! $transaction || $transaction->getTransactionId() !== $value || $transaction->getIsAuthTransaction()) {
            return false;
        }

        $lastCollectResponse = $transaction->getLastCollectResponse();

        if (! $lastCollectResponse || $lastCollectResponse->getStatus() !== Status::COMPLETE) {
            return false;
        }

        $completionData = $lastCollectResponse->getCompletionData();

        $digSig = new DigitalSignature(base64_decode($completionData->getSignature()));

        return base64_decode($digSig->getUserVisibleData()) === $this->userVisibleData;
    }

    public function message()
    {
        return 'The BankID signature is not valid.';
    }
}
