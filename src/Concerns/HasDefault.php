<?php

namespace Hewcode\Hewcode\Concerns;

trait HasDefault
{
    protected mixed $default = null;

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }
}
