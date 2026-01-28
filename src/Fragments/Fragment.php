<?php

namespace Hewcode\Hewcode\Fragments;

use JsonSerializable;

abstract class Fragment implements JsonSerializable
{
    public function toData(): array
    {
        return [
            '__hcf' => true,
        ];
    }

    public static function make(...$args): static
    {
        return new static(...$args);
    }

    public static function serialize(...$args): array
    {
        return static::make(...$args)->toData();
    }

    public function jsonSerialize(): array
    {
        return $this->toData();
    }
}
