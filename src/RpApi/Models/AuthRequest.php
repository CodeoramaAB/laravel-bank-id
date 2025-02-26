<?php

namespace Jgroup\BankID\RpApi\Models;

use Jgroup\BankID\Serializers\JsonSerializer;

class AuthRequest extends JsonSerializer
{
    public string $endUserIp;

    public ?string $returnUrl = null;

    public ?string $userVisibleData = null;

    public ?string $userVisibleDataFormat = null;

    public ?string $userNonVisibleData = null;

    public ?array $requirement = null;

    public function __construct(string $endUserIp)
    {
        $this->endUserIp = $endUserIp;
    }

    public function getEndUserIp(): string
    {
        return $this->endUserIp;
    }

    public function setEndUserIp(string $endUserIp): void
    {
        $this->endUserIp = $endUserIp;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): void
    {
        $this->returnUrl = $returnUrl;
    }

    public function getUserVisibleData(): ?string
    {
        return $this->userVisibleData;
    }

    public function setUserVisibleData(?string $userVisibleData): void
    {
        $this->userVisibleData = base64_encode($userVisibleData);
    }

    public function getUserVisibleDataFormat(): ?string
    {
        return $this->userVisibleDataFormat;
    }

    public function setUserVisibleDataFormat(?string $userVisibleDataFormat): void
    {
        $this->userVisibleDataFormat = $userVisibleDataFormat;
    }

    public function getUserNonVisibleData(): ?string
    {
        return $this->userNonVisibleData;
    }

    public function setUserNonVisibleData(?string $userNonVisibleData): void
    {
        $this->userNonVisibleData = base64_encode($userNonVisibleData);
    }

    public function getRequirement(): ?array
    {
        return $this->requirement;
    }

    public function setRequirement(?array $requirement): void
    {
        $this->requirement = $requirement;
    }
}
