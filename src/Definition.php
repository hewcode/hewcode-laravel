<?php

namespace Hewcode\Hewcode;

abstract class Definition
{
    use Concerns\HasContext;

    protected string $model;

    public function getModelClass(): ?string
    {
        return $this->model ?? null;
    }

    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }
}
