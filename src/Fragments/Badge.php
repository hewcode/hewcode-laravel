<?php

namespace Hewcode\Hewcode\Fragments;

class Badge extends Fragment
{
    public function __construct(
        protected ?string $label,
        protected ?string $color = null,
        protected ?array $icon = null,
        protected ?string $variant = null,
    ) {}

    public function toData(): array
    {
        return array_merge(parent::toData(), [
            '_badge' => [
                'variant' => $this->variant,
                'color' => $this->color,
                'icon' => $this->icon,
                'label' => Text::serialize($this->label),
            ],
        ]);
    }
}
