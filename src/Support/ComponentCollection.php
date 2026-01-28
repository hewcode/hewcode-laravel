<?php

namespace Hewcode\Hewcode\Support;

use Hewcode\Hewcode\Contracts\MountsComponents;

class ComponentCollection implements MountsComponents
{
    public function __construct(
        protected array $components
    ) {
    }

    public function getComponent(string $name): Component|ComponentCollection|null
    {
        return collect($this->components)->firstWhere(
            fn (Component $field) => $field->getName() === $name
        );
    }
}
