<?php

namespace Hewcode\Hewcode\Lists\Filters;

use Closure;
use Hewcode\Hewcode\Concerns\HasFormSchema;
use Hewcode\Hewcode\Concerns\HasLabel;
use Hewcode\Hewcode\Concerns\HasModel;
use Hewcode\Hewcode\Concerns\HasVisibility;
use Hewcode\Hewcode\Support\Component;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class Filter extends Component
{
    use HasFormSchema;
    use HasLabel;
    use HasModel;
    use HasVisibility;

    public string $field;
    public string $type = 'text';
    public array $rules = [];
    public ?Closure $filterUsing = null;

    protected mixed $resolvedState = null;

    public static function make(string $name): static
    {
        return (new static())->name($name)->field($name);
    }

    public function field(string $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function filterUsing(Closure $callback): static
    {
        $this->filterUsing = $callback;

        return $this;
    }

    public function resolveState(array $filterValues): static
    {
        if (array_key_exists($this->name, $filterValues)) {
            $this->resolvedState = $filterValues[$this->name];
        } else {
            $this->resolvedState = null;
        }

        return $this;
    }

    public function validate(): void
    {
        if (! empty($this->rules)) {
            // Validate resolved state directly
            validator(['filter.'.$this->name => $this->resolvedState], [
                'filter.'.$this->name => $this->rules,
            ])->validate();
        }
    }

    public function getState(): mixed
    {
        return $this->resolvedState;
    }

    public function filled(): bool
    {
        return $this->resolvedState !== null && $this->resolvedState !== '';
    }

    /**
     * Modify the query for Eloquent driver
     */
    public function modifyQuery(Builder|\Illuminate\Database\Eloquent\Builder $query, mixed $value): void
    {
        if (is_array($value)) {
            $query->whereIn($this->field, $value);
        } else {
            $query->where($this->field, $value);
        }
    }

    /**
     * Modify the collection for Iterable driver
     */
    public function modifyCollection(Collection $collection, mixed $value): Collection
    {
        return $collection->filter(function ($item) use ($value) {
            $itemValue = data_get($item, $this->field);

            if (is_array($value)) {
                if (is_array($itemValue)) {
                    // If both are arrays, check for intersection
                    return !empty(array_intersect($itemValue, $value));
                }
                // If filter value is array but item value isn't, check if item value is in filter array
                return in_array($itemValue, $value);
            }

            if (is_array($itemValue)) {
                // If item value is array but filter isn't, check if filter value is in item array
                return in_array($value, $itemValue);
            }

            // Simple equality check
            return $itemValue == $value;
        });
    }
}
