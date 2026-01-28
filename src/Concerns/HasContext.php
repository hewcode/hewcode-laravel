<?php

namespace Hewcode\Hewcode\Concerns;

use Hewcode\Hewcode\Support\Context;

trait HasContext
{
    protected ?Context $context = null;

    public function context(Context|string|array|null $context): static
    {
        if (is_string($context)) {
            $this->context = ($this->context ?? new Context)->context($context);
        } elseif ($context instanceof Context || is_null($context)) {
            $this->context = $context;
        } elseif (is_array($context)) {
            $this->context = ($this->context ?? new Context)->with($context);
        }

        return $this;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function withContext(string|array $key, mixed $value = null): static
    {
        $this->context->with($key, $value);

        return $this;
    }

    public function withPublicContext(string|array $key, mixed $value = null): static
    {
        $this->context->with($key, $value, true);

        return $this;
    }
}
