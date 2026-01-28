<?php

namespace Hewcode\Hewcode\Panel\Navigation;

class NavigationGroup extends NavigationItem
{
    use Concerns\HasItems;

    protected string $icon = 'lucide-folder';

    protected bool $collapsed = false;

    public static function make(): static
    {
        return new static();
    }

    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function toData(): array
    {
        $data = parent::toData();

        $data['collapsed'] = $this->collapsed;
        $data['items'] = array_map(
            fn ($item) => $item->toData(),
            $this->items
        );

        return $data;
    }
}
