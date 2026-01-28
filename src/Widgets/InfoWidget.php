<?php

namespace Hewcode\Hewcode\Widgets;

use Hewcode\Hewcode\Hewcode;

class InfoWidget extends Widget
{
    protected ?string $heading = null;

    protected ?string $description = null;

    protected ?string $icon = null;

    public function getType(): string
    {
        return 'info';
    }

    public function heading(?string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function toData(): array
    {
        if ($icon = $this->icon) {
            Hewcode::registerIcon($icon);
        }

        return array_merge(parent::toData(), [
            'heading' => $this->heading,
            'description' => $this->description,
            'icon' => $icon,
        ]);
    }
}
