<?php

namespace Hewcode\Hewcode\Widgets;

use Closure;
use Hewcode\Hewcode\Actions\Action;
use Hewcode\Hewcode\Support\Enums\Color;

class CardWidget extends Widget
{
    protected ?string $heading = null;

    protected ?Closure $contentUsing = null;

    protected ?string $icon = null;

    protected ?Color $color = null;

    /** @var Action[] */
    protected array $actions = [];

    public function getType(): string
    {
        return 'card';
    }

    public function heading(?string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function content(Closure|string $content): static
    {
        if ($content instanceof Closure) {
            $this->contentUsing = $content;
        } else {
            $this->contentUsing = fn () => $content;
        }

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

    public function actions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    protected function getContent(): ?string
    {
        if (! $this->contentUsing) {
            return null;
        }

        return $this->evaluate($this->contentUsing);
    }

    public function toData(): array
    {
        return array_merge(parent::toData(), [
            'heading' => $this->heading,
            'content' => $this->getContent(),
            'icon' => $this->icon,
            'color' => $this->color?->value,
            'actions' => collect($this->actions)
                ->filter(fn (Action $action) => $action->isVisible())
                ->mapWithKeys(fn (Action $action) => [
                    $action->getName() => $action->parent($this)->toData(),
                ])
                ->toArray(),
        ]);
    }
}
