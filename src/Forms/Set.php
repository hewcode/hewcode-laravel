<?php

namespace Hewcode\Hewcode\Forms;

class Set
{
    public function __construct(
        protected array &$modifications
    ) {}

    public function __invoke(string $key, mixed $value): void
    {
        $this->modifications[$key] = $value;
    }
}
