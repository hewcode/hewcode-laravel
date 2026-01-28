<?php

namespace Hewcode\Hewcode\Widgets;

use Closure;
use Hewcode\Hewcode\Concerns\HasVisibility;
use Hewcode\Hewcode\Concerns\HasLabel;
use Hewcode\Hewcode\Support\Component;

abstract class Widget extends Component
{
    use HasVisibility;
    use HasLabel;

    protected ?string $description = null;

    protected mixed $value = null;

    protected ?Closure $valueUsing = null;

    protected ?int $pollingInterval = null;

    protected int $colspan = 1;

    abstract public function getType(): string;

    public function __construct()
    {
        $this->setUp();
    }

    public function setUp(): void
    {
        //
    }

    public static function make(string $name): static
    {
        return (new static())->name($name);
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function value(mixed $value): static
    {
        if ($value instanceof Closure) {
            $this->valueUsing = $value;
        } else {
            $this->value = $value;
        }

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->evaluate($this->valueUsing) ?? $this->value;
    }

    public function poll(int $seconds): static
    {
        $this->pollingInterval = $seconds * 1000;

        return $this;
    }

    public function colspan(int $colspan): static
    {
        $this->colspan = $colspan;

        return $this;
    }

    public function toData(): array
    {
        return array_merge(parent::toData(), [
            'type' => $this->getType(),
            'label' => $this->getLabel(),
            'description' => $this->description,
            'value' => $this->getValue(),
            'refreshInterval' => $this->pollingInterval,
            'colspan' => $this->colspan,
        ]);
    }
}
