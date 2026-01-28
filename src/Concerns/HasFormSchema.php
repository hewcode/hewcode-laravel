<?php

namespace Hewcode\Hewcode\Concerns;

use Closure;

trait HasFormSchema
{
    protected Closure|array $formSchema = [];

    public function form(Closure|array $schema): static
    {
        $this->formSchema = $schema;

        return $this;
    }

    public function getFormSchema(): array
    {
        if (is_callable($this->formSchema)) {
            return $this->evaluate($this->formSchema);
        }

        return $this->formSchema;
    }
}
