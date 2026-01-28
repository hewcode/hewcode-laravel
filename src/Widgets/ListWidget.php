<?php

namespace Hewcode\Hewcode\Widgets;

use Closure;

class ListWidget extends Widget
{
    protected ?Closure $itemsUsing = null;

    protected array $schema = [];

    protected ?string $emptyState = null;

    protected ?int $limit = null;

    protected ?Closure $actionUsing = null;

    public function getType(): string
    {
        return 'list';
    }

    public function items(Closure $callback): static
    {
        $this->itemsUsing = $callback;

        return $this;
    }

    public function schema(array $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    public function emptyState(?string $emptyState): static
    {
        $this->emptyState = $emptyState;

        return $this;
    }

    public function limit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function action(?Closure $action): static
    {
        $this->actionUsing = $action;

        return $this;
    }

    protected function getItems(): array
    {
        if (! $this->itemsUsing) {
            return [];
        }

        $items = $this->evaluate($this->itemsUsing);

        if ($this->limit) {
            $items = collect($items)->take($this->limit);
        }

        return collect($items)->map(function ($item) {
            $mapped = [];

            foreach ($this->schema as $key => $callback) {
                $mapped[$key] = $callback instanceof Closure
                    ? $callback($item)
                    : data_get($item, $callback);
            }

            return $mapped;
        })->all();
    }

    public function toData(): array
    {
        return array_merge(parent::toData(), [
            'items' => $this->getItems(),
            'emptyState' => $this->emptyState,
            'hasAction' => $this->actionUsing !== null,
        ]);
    }
}
