<?php

namespace Hewcode\Hewcode\Fragments;

class Text extends Fragment
{
    public function __construct(
        public mixed $value
    ) {}

    public function toData(): array
    {
        return array_merge(parent::toData(), [
            '_text' => $this->value,
        ]);
    }
}
