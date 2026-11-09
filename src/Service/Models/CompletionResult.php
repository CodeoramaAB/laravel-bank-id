<?php

namespace Jgroup\BankID\Service\Models;

class CompletionResult
{
    protected string $name;

    protected string $personalNumber;

    protected ?string $signedText = null;

    public function __construct(string $name, string $personalNumber, ?string $signedText = null)
    {
        $this->name = $name;
        $this->personalNumber = $personalNumber;
        $this->signedText = $signedText;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPersonalNumber(): string
    {
        return $this->personalNumber;
    }

    public function getSignedText(): ?string
    {
        return $this->signedText;
    }
}
