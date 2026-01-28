<?php

namespace Hewcode\Hewcode\Concerns;

use RuntimeException;

trait RequiresVisibility
{
    use HasVisibility {
        HasVisibility::isVisible as hasVisibilityIsVisible;
    }

    public function isVisible(): bool
    {
        if ($this->visible === null) {
            throw new RuntimeException('Visibility must be explicitly set using the ->visible() method for '.static::class.'.');
        }

        return $this->hasVisibilityIsVisible();
    }
}
