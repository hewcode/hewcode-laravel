<?php

namespace Hewcode\Hewcode\Forms\Schema;

use BackedEnum;
use Closure;
use Hewcode\Hewcode\Concerns;
use Hewcode\Hewcode\Contracts;
use Hewcode\Hewcode\Forms\Set;
use Hewcode\Hewcode\Support\Component;

abstract class Field extends Component implements Contracts\HasVisibility, Contracts\HasRecord
{
    use Concerns\HasLabel;
    use Concerns\HasPlaceholder;
    use Concerns\HasDefault;
    use Concerns\HasModel;
    use Concerns\HasVisibility;
    use Concerns\HasValidationRules;
    use Concerns\HasRecord;

    protected bool $dehydrated = true;
    protected bool $reactive = false;
    protected ?Closure $formatStateUsing = null;
    protected ?Closure $dehydrateStateUsing = null;
    protected ?Closure $saveUsing = null;
    protected ?Closure $onStateUpdate = null;

    public function __construct()
    {
        $this->setUp();
    }

    protected function setUp(): void
    {
        //
    }

    public static function make(string $name): static
    {
        return (new static())->name($name);
    }

    public function dehydrated(bool $dehydrated = true): static
    {
        $this->dehydrated = $dehydrated;

        return $this;
    }

    public function reactive(bool $reactive = true): static
    {
        $this->reactive = $reactive;

        return $this;
    }

    public function onStateUpdate(Closure $callback): static
    {
        $this->onStateUpdate = $callback;

        return $this;
    }

    public function formatStateUsing(Closure $callback): static
    {
        $this->formatStateUsing = $callback;

        return $this;
    }

    public function dehydrateStateUsing(Closure $callback): static
    {
        $this->dehydrateStateUsing = $callback;

        return $this;
    }

    public function saveUsing(Closure $callback): static
    {
        $this->saveUsing = $callback;

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getDehydrated(): bool
    {
        return $this->dehydrated;
    }

    public function formatState(mixed $state): mixed
    {
        if ($this->formatStateUsing) {
            return $this->evaluate($this->formatStateUsing, ['state' => $state]);
        }

        if ($state instanceof BackedEnum) {
            return property_exists($state, 'value') ? $state->value : $state->name;
        }

        return $state;
    }

    public function dehydrateState(mixed $state, array $data, array $params): mixed
    {
        if (! $this->dehydrated) {
            return $state;
        }

        if ($this->dehydrateStateUsing) {
            return $this->evaluate($this->dehydrateStateUsing, array_merge([
                'state' => $state,
                'data' => $data,
            ], $params));
        }

        return $state;
    }

    public function saveState(mixed $state, array $data): void
    {
        if (! $this->saveUsing) {
            return;
        }

        $this->evaluate($this->saveUsing, [
            'state' => $state,
            'data' => $data,
        ]);
    }

    public function executeStateUpdate(array $state): array
    {
        if (! $this->onStateUpdate) {
            return [];
        }

        $modifications = [];

        $set = new Set($modifications);

        $this->evaluate($this->onStateUpdate, [
            'set' => $set,
            'state' => $state,
        ]);

        return $modifications;
    }

    public function isReactive(): bool
    {
        return $this->reactive;
    }

    /**
     * Get field-specific data to merge with base field data
     * Override in subclasses to add field-type-specific properties
     */
    protected function getFieldSpecificData(): array
    {
        return [];
    }

    /**
     * Get the field type identifier (e.g., 'text-input', 'select', 'textarea')
     */
    abstract protected function getFieldType(): string;

    /**
     * Base toData that combines common field data with field-specific data
     */
    public function toData(): array
    {
        return array_merge(parent::toData(), [
            'type' => $this->getFieldType(),
            'label' => $this->getLabel(),
            'placeholder' => $this->getPlaceholder(),
            'default' => $this->getDefault(),
            'required' => $this->isRequired(),
            'reactive' => $this->reactive,
        ], $this->getFieldSpecificData());
    }
}
