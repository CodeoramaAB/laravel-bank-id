<?php

namespace Jgroup\BankID\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Jgroup\BankID\Service\Models\Http\TransactionResponse|null auth(string $endUserIp, string $userVisibleData = null, string $userVisibleDataFormat = null, string $userNonVisibleData = null, array $requirement = null)
 * @method static \Jgroup\BankID\Service\Models\Http\TransactionResponse|null sign(string $endUserIp, string $userVisibleData, string $userVisibleDataFormat = null, string $userNonVisibleData = null, array $requirement = null)
 * @method static \Jgroup\BankID\Service\Models\Http\CollectResponse|null collect()
 * @method static \Jgroup\BankID\Service\Models\Http\CancelResponse cancel()
 * @method static \Jgroup\BankID\Service\Models\BankIDTransaction|null getSessionTransaction()
 * @method static void setSessionTransaction(?\Jgroup\BankID\Service\Models\BankIDTransaction $transaction = null)
 *
 * @see \Jgroup\BankID\Service\BankIDService
 */
class BankID extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'bankid';
    }
}
