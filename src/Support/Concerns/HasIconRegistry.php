<?php

namespace Hewcode\Hewcode\Support\Concerns;

/**
 * Provides icon registry functionality for efficient SVG serialization.
 *
 * Instead of duplicating SVG content for each usage, this trait collects
 * unique icon names and their SVG content into a registry that can be
 * serialized once and referenced multiple times on the frontend.
 */
trait HasIconRegistry
{
    /** @var array<string, string> */
    protected array $iconRegistry = [];

    /**
     * Register an icon for efficient serialization.
     *
     * @param string|null $iconName The icon name (e.g., 'lucide-circle-dot')
     * @return array{name: string, svg?: string}|null Icon reference for frontend
     */
    protected function registerIcon(?string $iconName): ?array
    {
        if (!$iconName) {
            return null;
        }

        // Register the SVG only once (deduplication)
        if (!isset($this->iconRegistry[$iconName])) {
            $this->iconRegistry[$iconName] = svg($iconName)->toHtml();
        }

        return ['name' => $iconName];
    }

    /**
     * Get all registered icons.
     *
     * @return array<string, string>
     */
    protected function getIconRegistry(): array
    {
        return $this->iconRegistry;
    }

    /**
     * Clear the icon registry.
     */
    protected function clearIconRegistry(): void
    {
        $this->iconRegistry = [];
    }
}
