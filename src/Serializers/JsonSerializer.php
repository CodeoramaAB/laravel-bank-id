<?php

namespace Jgroup\BankID\Serializers;

use Closure;
use JsonSerializable;

class JsonSerializer implements JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function toArray()
    {
        $publicProperties = Closure::fromCallable('get_object_vars')->__invoke($this);

        return array_filter(
            (array) $publicProperties,
            function ($value) {
                return $value !== null;
            }
        );
    }
}
