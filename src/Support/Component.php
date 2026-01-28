<?php

namespace Hewcode\Hewcode\Support;

use Hewcode\Hewcode\Concerns\EvaluatesClosures;

class Component
{
    use EvaluatesClosures;

    protected ?string $name = null;

    protected ?string $collectionName = null;

    protected ?self $parent = null;

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function parent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getPath(): string
    {
        $name = $this->getName();

        if ($this->collectionName) {
            $name = $this->collectionName . '.' . $name;
        }

        if ($this->parent) {
            return $this->parent->getPath() . '.' . $name;
        }

        return $name;
    }

    public function componentCollection(string $collectionName): self
    {
        $this->collectionName = $collectionName;

        return $this;
    }

    public function toData(): array
    {
        return [
            'path' => $this->getPath(),
            'name' => $this->getName(),
        ];
    }
}
