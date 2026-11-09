<?php

namespace Jgroup\BankID\Service\Models\Http;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Jgroup\BankID\Serializers\JsonSerializer;
use Jgroup\BankID\Service\Models\CollectResult;

class CollectResponse extends JsonSerializer implements Responsable
{
    public string $transactionId;

    public ?string $hintCode = null;

    public ?string $status = null;

    public ?string $qrCode = null;

    protected array $customData = [];

    protected CollectResult $collectResult;

    public ?CompletionResponse $completionResponse = null;

    public function __construct(string $transactionId, CollectResult $collectResult)
    {
        $this->transactionId = $transactionId;
        $this->hintCode = $collectResult->getHintCode();
        $this->status = $collectResult->getStatus();
        $this->qrCode = $collectResult->getQrCode();
        $this->collectResult = $collectResult;

        if ($collectResult->getCompletionResult()) {
            $this->completionResponse = new CompletionResponse($collectResult->getCompletionResult());
        }
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getHintCode(): ?string
    {
        return $this->hintCode;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function getCollectResult(): CollectResult
    {
        return $this->collectResult;
    }

    public function getCompletionResponse(): ?CompletionResponse
    {
        return $this->completionResponse;
    }

    public function withData(array $data): self
    {
        $this->customData = $data;

        return $this;
    }

    public function toResponse($request): Response
    {
        return new Response(
            json_encode(array_merge($this->customData, $this->toArray())),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
