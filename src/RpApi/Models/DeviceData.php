<?php

namespace Jgroup\BankID\RpApi\Models;

class DeviceData
{
    protected ?string $ipAddress = null;

    protected ?string $uhi = null;

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getUhi(): ?string
    {
        return $this->uhi;
    }

    public function setUhi(string $uhi): void
    {
        $this->uhi = $uhi;
    }
}
