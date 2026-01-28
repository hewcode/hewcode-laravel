<?php

namespace Hewcode\Hewcode\Widgets;

use Closure;

class ChartWidget extends Widget
{
    protected ?string $chartType = 'line';

    protected ?Closure $dataUsing = null;

    protected array $chartData = [];

    protected ?int $height = null;

    protected array $options = [];

    protected array $colors = [];

    public function getType(): string
    {
        return 'chart';
    }

    public function chartType(string $type): static
    {
        $this->chartType = $type;

        return $this;
    }

    public function data(array|Closure $data): static
    {
        if ($data instanceof Closure) {
            $this->dataUsing = $data;
        } else {
            $this->chartData = $data;
        }

        return $this;
    }

    public function height(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    protected function getChartData(): array
    {
        if ($this->dataUsing) {
            return $this->evaluate($this->dataUsing);
        }

        return $this->chartData;
    }

    public function toData(): array
    {
        return array_merge(parent::toData(), [
            'chartType' => $this->chartType,
            'chartData' => $this->getChartData(),
            'height' => $this->height,
            'options' => $this->options,
            'colors' => $this->colors,
        ]);
    }
}
