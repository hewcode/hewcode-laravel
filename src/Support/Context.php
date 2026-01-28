<?php

namespace Hewcode\Hewcode\Support;

use Illuminate\Support\Collection;

class Context
{
    protected ?string $context = null;

    protected Collection $internalBag;
    protected Collection $publicBag;

    public function __construct()
    {
        $this->internalBag = new Collection();
        $this->publicBag = new Collection();
    }

    public function context(?string $context = null): ?static
    {
        $this->context = $context;

        return $this;
    }

    public function is(?string $context): bool
    {
        return $this->context === $context;
    }

    public function isNot(?string $context): bool
    {
        return ! $this->is($context);
    }

    public function isDefault(): bool
    {
        return $this->is(null);
    }

    public function isNotDefault(): bool
    {
        return ! $this->isDefault();
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function with(string|array $key, mixed $value = null, bool $public = false): static
    {
        $bag = $public ? $this->publicBag : $this->internalBag;

        if (is_array($key)) {
            $bag->merge($key);
        } else {
            $bag->put($key, $value);
        }

        return $this;
    }

    public function without(string|array $key): static
    {
        $this->internalBag->forget((array) $key);
        $this->publicBag->forget((array) $key);

        return $this;
    }

    public function getBag(): Collection
    {
        return $this->internalBag->merge($this->publicBag);
    }

    public function getPublicBag(): Collection
    {
        return $this->publicBag;
    }

    public function getInternalBag(): Collection
    {
        return $this->internalBag;
    }
}
