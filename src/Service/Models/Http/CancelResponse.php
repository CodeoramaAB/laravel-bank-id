<?php

namespace Jgroup\BankID\Service\Models\Http;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Jgroup\BankID\Serializers\JsonSerializer;

class CancelResponse extends JsonSerializer implements Responsable
{
    protected bool $wasCancelledSuccessfully;

    public function __construct(bool $wasCancelledSuccessfully)
    {
        $this->wasCancelledSuccessfully = $wasCancelledSuccessfully;
    }

    public function getWasCancelledSuccessfully(): bool
    {
        return $this->wasCancelledSuccessfully;
    }

    public function toResponse($request): Response
    {
        $status = $this->wasCancelledSuccessfully ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        return new Response(
            json_encode($this->toArray()),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}
