<?php

namespace Hewcode\Hewcode\Support;

/**
 * Global icon registry for efficient SVG serialization.
 *
 * Instead of duplicating SVG content for each usage, this service collects
 * unique icon names and their SVG content into a registry that can be
 * serialized once and referenced multiple times on the frontend.
 */
class IconRegistry
{
    /** @var array<string, string> */
    protected array $icons = [];

    /**
     * Register an icon for efficient serialization.
     *
     * @param string|null $iconName The icon name (e.g., 'lucide-circle-dot')
     * @return array{name: string}|null Icon reference for frontend
     */
    public function register(?string $iconName): ?array
    {
        if (! $iconName) {
            return null;
        }

        if (! isset($this->icons[$iconName])) {
            $this->icons[$iconName] = svg($iconName)->toHtml();
        }

        return ['name' => $iconName];
    }

    /**
     * Get all registered icons.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->icons;
    }

    /**
     * Clear the icon registry.
     */
    public function clear(): void
    {
        $this->icons = [];
    }

    /**
     * Check if an icon is registered.
     */
    public function has(string $iconName): bool
    {
        return isset($this->icons[$iconName]);
    }

    /**
     * Get a specific icon's SVG.
     */
    public function get(string $iconName): ?string
    {
        return $this->icons[$iconName] ?? null;
    }
}
