<?php

namespace Hewcode\Hewcode\Panel\Navigation;

use Hewcode\Hewcode\Hewcode;

class Navigation
{
    use Concerns\HasItems;

    protected string $panel;

    public function __construct(string $panel)
    {
        $this->panel = $panel;
    }

    /**
     * Serialize the navigation to an array.
     */
    public function toData(): array
    {
        $items = collect($this->items)
            ->sortBy(fn (NavigationItem $item) => $item->getOrder() ?? PHP_INT_MAX)
            ->map(fn (NavigationItem $item) => $this->processItem($item))
            ->values()
            ->all();

        return [
            'items' => $items,
        ];
    }

    /**
     * Recursively process an item and register all icons.
     */
    protected function processItem(NavigationItem $item): array
    {
        $data = $item->toData();

        // Register icon and replace with reference
        if (isset($data['icon'])) {
            $data['icon'] = Hewcode::registerIcon($data['icon']);
        }

        // Recursively process nested items
        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = array_map(function ($nestedItemData) {
                // Register nested icons
                if (isset($nestedItemData['icon'])) {
                    $nestedItemData['icon'] = Hewcode::registerIcon($nestedItemData['icon']);
                }

                // Recursively process deeper nesting
                if (isset($nestedItemData['items']) && is_array($nestedItemData['items'])) {
                    $nestedItemData['items'] = array_map(function ($deepItemData) {
                        return $this->processNestedItem($deepItemData);
                    }, $nestedItemData['items']);
                }

                return $nestedItemData;
            }, $data['items']);
        }

        return $data;
    }

    /**
     * Recursively process deeply nested items.
     */
    protected function processNestedItem(array $itemData): array
    {
        if (isset($itemData['icon'])) {
            $itemData['icon'] = Hewcode::registerIcon($itemData['icon']);
        }

        if (isset($itemData['items']) && is_array($itemData['items'])) {
            $itemData['items'] = array_map(
                fn ($nestedData) => $this->processNestedItem($nestedData),
                $itemData['items']
            );
        }

        return $itemData;
    }

    public function getPanel(): string
    {
        return $this->panel;
    }
}
