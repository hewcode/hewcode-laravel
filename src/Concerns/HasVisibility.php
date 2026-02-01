<?php

namespace Hewcode\Hewcode\Concerns;

use Closure;

trait HasVisibility
{
    protected bool|Closure|null $visible = null;

    protected bool|Closure|null $hidden = null;

    public function visible(bool|Closure $visible = true): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function hidden(bool|Closure $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function isVisible(): bool
    {
        $visible = true;
        $hidden = false;

        if (is_bool($this->hidden)) {
            $hidden = $this->hidden;
        }

        if ($this->hidden instanceof Closure) {
            $hidden = $this->evaluate($this->hidden);
        }

        if (is_bool($this->visible)) {
            $visible = $this->visible;
        }

        if ($this->visible instanceof Closure) {
            $visible = (bool) $this->evaluate($this->visible);
        }

        return $visible && ! $hidden;
    }
}
