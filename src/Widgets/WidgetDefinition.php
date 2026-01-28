<?php

namespace Hewcode\Hewcode\Widgets;

use Closure;
use Hewcode\Hewcode\Definition;
use RuntimeException;

abstract class WidgetDefinition extends Definition
{
    protected ?Closure $touchUsing = null;

    protected string $widgetClass;

    public function touch(?Closure $touchUsing): static
    {
        $this->touchUsing = $touchUsing;

        return $this;
    }

    public function default(Widget $widget): Widget
    {
        return $widget;
    }

    public function create(string $name = '', string $context = 'default'): Widget
    {
        $widgetClass = $this->widgetClass ?? $this->inferWidgetClass();

        $widget = $widgetClass::make($name)->context($this->context);

        if ($this->touchUsing) {
            ($this->touchUsing)($widget);
        }

        return match ($context) {
            'default' => $this->default($widget),
            default => throw new RuntimeException("Unknown widget context: $context"),
        };
    }

    public static function make(string $name, string $context = 'default'): Widget
    {
        return app(static::class)->create($name, $context);
    }

    protected function inferWidgetClass(): string
    {
        if (! isset($this->widgetClass)) {
            throw new RuntimeException('Widget class must be specified in the $widgetClass property or overridden in inferWidgetClass()');
        }

        return $this->widgetClass;
    }
}
