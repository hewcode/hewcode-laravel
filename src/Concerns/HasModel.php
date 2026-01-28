<?php

namespace Hewcode\Hewcode\Concerns;

use InvalidArgumentException;

trait HasModel
{
    protected object|null $model = null;

    public function model(object|string|null $model): static
    {
        if (is_string($model) && class_exists($model)) {
            $model = new $model();
        }

        if (is_string($model)) {
            throw new InvalidArgumentException("Model class '$model' does not exist.");
        }

        $this->model = $model;

        return $this;
    }

    public function getModel(): object|null
    {
        return $this->model;
    }
}
