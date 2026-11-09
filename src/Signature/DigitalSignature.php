<?php

namespace Jgroup\BankID\Signature;

use Exception;
use SimpleXMLElement;

class DigitalSignature
{
    protected ?string $signatureUsage = null;

    protected ?string $userNonVisibleData = null;

    protected ?string $userVisibleData = null;

    public function __construct(?string $xmlSignature)
    {
        if (! $xmlSignature) {
            throw new Exception('xmlSignature is null');
        }

        $xmlDoc = new SimpleXMLElement($xmlSignature);

        $xmlDoc->registerXPathNamespace('digsig', 'http://www.w3.org/2000/09/xmldsig#');
        $xmlDoc->registerXPathNamespace('bidsig', 'http://www.bankid.com/signature/v1.0.0/types');

        $bidSignedData = '/digsig:Signature/digsig:Object/bidsig:bankIdSignedData';

        $signatureUsage = $xmlDoc->xpath($bidSignedData.'/bidsig:clientInfo/bidsig:funcId');

        if (count($signatureUsage) > 0) {
            $this->signatureUsage = (string) $signatureUsage[0];
        }

        $userNonVisibleData = $xmlDoc->xpath($bidSignedData.'/bidsig:usrNonVisibleData');

        if (count($userNonVisibleData) > 0) {
            $this->userNonVisibleData = (string) $userNonVisibleData[0];
        }

        $userVisibleData = $xmlDoc->xpath($bidSignedData.'/bidsig:usrVisibleData');

        if (count($userVisibleData) > 0) {
            $this->userVisibleData = (string) $userVisibleData[0];
        }

        // TODO: handle errors
    }

    public function getSignatureUsage(): ?string
    {
        return $this->signatureUsage;
    }

    public function getUserNonVisibleData(): ?string
    {
        return $this->userNonVisibleData;
    }

    public function getUserVisibleData(): ?string
    {
        return $this->userVisibleData;
    }
}
