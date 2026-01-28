<?php

namespace Hewcode\Hewcode\Contracts;

use Hewcode\Hewcode\Support\Component;
use Hewcode\Hewcode\Support\ComponentCollection;

interface MountsComponents
{
    public function getComponent(string $name): Component|ComponentCollection|null;
}
