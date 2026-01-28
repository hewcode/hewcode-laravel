<?php

namespace Hewcode\Hewcode\Panel\Navigation;

class NavigationItem
{
    protected ?string $label = null;

    /** @var string|(\Closure(): string)|null */
    protected string|\Closure|null $url = null;

    protected string $icon = 'lucide-circle-dot';

    protected bool $active = false;

    protected ?int $order = null;

    public static function make(): static
    {
        return new static();
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param string|(\Closure(): string)|null $url
     */
    public function url(string|\Closure|null $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function active(bool $active = true): static
    {
        $this->active = $active;

        return $this;
    }

    public function order(?int $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): ?string
    {
        if ($this->url instanceof \Closure) {
            return ($this->url)();
        }

        return $this->url;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function toData(): array
    {
        $data = [
            'label' => $this->label,
            'icon' => $this->icon,
            'active' => $this->active,
            'order' => $this->order,
        ];

        $url = $this->getUrl();

        if ($url !== null) {
            $data['url'] = $url;
        }

        return $data;
    }
}
