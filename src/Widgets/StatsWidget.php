<?php

namespace Hewcode\Hewcode\Widgets;

use Closure;
use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Support\Enums\Color;

class StatsWidget extends Widget
{
    protected ?string $format = null;

    protected ?string $icon = null;

    protected ?Color $color = null;

    protected array|Closure|null $trend = null;

    protected ?string $trendLabel = null;

    public function getType(): string
    {
        return 'stats';
    }

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(Color|string|null $color): static
    {
        if ($color instanceof Color) {
            $this->color = $color;
        } elseif (is_string($color)) {
            $this->color = Color::from($color);
        } else {
            $this->color = null;
        }

        return $this;
    }

    public function trend(array|Closure $trend): static
    {
        $this->trend = $trend;

        return $this;
    }

    public function trendLabel(?string $trendLabel): static
    {
        $this->trendLabel = $trendLabel;

        return $this;
    }

    protected function getTrend(): ?array
    {
        $trend = $this->evaluate($this->trend);

        if ($trend && $this->trendLabel) {
            $trend['label'] = $this->trendLabel;
        }

        return $trend;
    }

    protected function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return match($this->format) {
            'currency' => '$' . number_format((float) $value, 2),
            'percentage' => number_format((float) $value, 1) . '%',
            'number' => number_format((float) $value),
            default => (string) $value,
        };
    }

    public function toData(): array
    {
        $value = $this->getValue();

        if ($icon = $this->icon) {
            Hewcode::registerIcon($icon);
        }

        return array_merge(parent::toData(), [
            'formatted_value' => $this->formatValue($value),
            'icon' => $this->icon,
            'color' => $this->color?->value,
            'trend' => $this->getTrend(),
        ]);
    }
}
