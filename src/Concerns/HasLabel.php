<?php

namespace Hewcode\Hewcode\Concerns;

use function Hewcode\Hewcode\generateFieldLabel;
use function Hewcode\Hewcode\resolveLocaleLabel;

trait HasLabel
{
    protected ?string $label = null;

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }

        if (method_exists($this, 'getModel') && $this->getModel()) {
            return resolveLocaleLabel($this->getName(), $this->getModel());
        }

        return generateFieldLabel($this->getName());
    }
}
