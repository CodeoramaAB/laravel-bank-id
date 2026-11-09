<?php

namespace Jgroup\BankID\Service\Models\Http;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Jgroup\BankID\Serializers\JsonSerializer;

class TransactionResponse extends JsonSerializer implements Responsable
{
    public string $transactionId;

    public string $autoStartToken;

    public function __construct(string $transactionId, string $autoStartToken)
    {
        $this->transactionId = $transactionId;
        $this->autoStartToken = $autoStartToken;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getAutoStartToken(): string
    {
        return $this->autoStartToken;
    }

    public function toResponse($request): Response
    {
        return new Response(
            json_encode($this->toArray()),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
