<?php

namespace Hewcode\Hewcode\Concerns;

use Hewcode\Hewcode\Forms\FormDefinition;

trait HasFormDefinition
{
    protected ?FormDefinition $formDefinition = null;

    public function usingForm(?FormDefinition $formDefinition): self
    {
        $this->formDefinition = $formDefinition;

        return $this;
    }

    public function getFormDefinition(): ?FormDefinition
    {
        return $this->formDefinition?->context($this->context);
    }
}
