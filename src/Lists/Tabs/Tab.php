<?php

namespace Hewcode\Hewcode\Lists\Tabs;

use Closure;
use Hewcode\Hewcode\Concerns\EvaluatesClosures;
use Hewcode\Hewcode\Hewcode;

class Tab
{
    use EvaluatesClosures;

    public string $name;
    public string $label;
    public Closure|string|null $icon = null;
    public Closure|string|null $badge = null;
    public Closure|null $query = null;
    public bool $active = false;

    public static function make(string $name): static
    {
        return (new static())->name($name);
    }

    public function name(string $name): static
    {
        $this->name = $name;
        $this->label ??= ucfirst($name);

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(Closure|string|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function badge(Closure|string|null $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    public function query(?Closure $query): static
    {
        $this->query = $query;

        return $this;
    }

    public function active(bool $active = true): static
    {
        $this->active = $active;

        return $this;
    }

    protected function getIcon(): ?string
    {
        if (! is_callable($this->icon)) {
            return $this->icon;
        }

        return $this->evaluate($this->icon);
    }

    protected function getBadge(): ?string
    {
        if (! is_callable($this->badge)) {
            return $this->badge;
        }

        return $this->evaluate($this->badge);
    }

    protected function getQuery(): ?Closure
    {
        return $this->query;
    }

    protected function getEvaluationParameters(): array
    {
        return [];
    }

    public function toData(): array
    {
        if ($icon = $this->getIcon()) {
            Hewcode::registerIcon($icon);
        }

        return [
            'name' => $this->name,
            'label' => $this->label,
            'icon' => $icon,
            'badge' => $this->getBadge(),
            'active' => $this->active,
        ];
    }
}
