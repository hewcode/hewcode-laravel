<?php

namespace Hewcode\Hewcode\Panel\Navigation\Concerns;

use Hewcode\Hewcode\Panel\Navigation\NavigationItem;

trait HasItems
{
    /** @var NavigationItem[] */
    protected array $items = [];

    /**
     * Add an item.
     */
    public function item(NavigationItem $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Add multiple items.
     *
     * @param  array<NavigationItem>  $items
     */
    public function items(array $items): static
    {
        $this->items = array_merge($this->items, $items);

        return $this;
    }

    /**
     * Get all items.
     *
     * @return array<NavigationItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
