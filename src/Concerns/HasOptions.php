<?php

namespace Hewcode\Hewcode\Concerns;

use BackedEnum;
use Closure;
use Hewcode\Hewcode\Support\Expose;
use Hewcode\Hewcode\Support\RelationResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasOptions
{
    public array|Closure|null $options = [];

    public bool $multiple = false;

    public bool $searchable = false;

    public ?Closure $searchResultsUsing = null;

    public int $preloadLimit = 0;

    public ?string $relationshipName = null;

    public ?string $relationshipTitleColumn = null;

    public ?Closure $relationshipModifyQueryUsing = null;

    public function searchResultsUsing(?Closure $callback): self
    {
        $this->searchResultsUsing = $callback;

        return $this;
    }

    public function preload(int $limit = 25): self
    {
        $this->preloadLimit = $limit;

        return $this;
    }

    public function options(array|Closure|string $options): self
    {
        if (is_string($options) && enum_exists($options)) {
            /** @var class-string<BackedEnum> $options */
            $this->options = collect($options::cases())
                ->mapWithKeys(function ($case) {
                    if (method_exists($case, 'getLabel')) {
                        return [$case->value => $case->getLabel()];
                    }

                    if (property_exists($case, 'name')) {
                        return [$case->value => $case->name];
                    }

                    return [$case->value => $case->value];
                })
                ->toArray();
        } elseif (is_callable($options)) {
            $this->options = $options;
        } else {
            $this->options = (array) $options;
        }

        return $this;
    }

    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function relationship(?string $relationshipName, ?string $titleColumn = 'name', ?Closure $modifyQueryUsing = null): self
    {
        if (! $relationshipName) {
            return $this;
        }

        $this->relationshipName = $relationshipName;
        $this->relationshipTitleColumn = $titleColumn;
        $this->relationshipModifyQueryUsing = $modifyQueryUsing;

        $this->searchResultsUsing(function (string $search) use ($relationshipName, $titleColumn, $modifyQueryUsing) {
            return $this->searchRelationship($relationshipName, $titleColumn, $search, $modifyQueryUsing);
        });

        $this->options(function () use ($relationshipName, $titleColumn, $modifyQueryUsing) {
            return $this->preloadRelationship($relationshipName, $titleColumn, $modifyQueryUsing);
        });

        if (method_exists($this, 'dehydrated')) {
            $this->dehydrated(false);
        }

        if (method_exists($this, 'saveUsing')) {
            $this->saveUsing(function ($record, $state, $data) use ($relationshipName) {
                $this->saveRelationshipUsing($record, $relationshipName, $state, $data);
            });
        }

        return $this;
    }

    #[Expose]
    public function getSearchResults(string $query): array
    {
        if (! $this->searchable || ! $this->searchResultsUsing) {
            return [];
        }

        return $this->evaluate($this->searchResultsUsing, [
            'search' => $query,
        ]) ?? [];
    }

    public function getOptions(): array
    {
        if (is_callable($this->options)) {
            $options = $this->evaluate($this->options) ?? [];
        } else {
            $options = $this->options ?? [];
        }

        return collect($options)
            ->map(function ($label, $value) {
                return ['label' => $label, 'value' => $value];
            })
            ->values()
            ->toArray();
    }

    public function getMultiple(): bool
    {
        return $this->multiple;
    }

    public function getSearchable(): bool
    {
        return $this->searchable;
    }

    public function searchRelationship(string $relationshipName, string $titleColumn, string $search, ?Closure $modifyQueryUsing): array
    {
        $relationshipQuery = $this->getRelationshipQuery($relationshipName);

        if (! $relationshipQuery) {
            return [];
        }

        $query = $relationshipQuery
            ->where($titleColumn, 'like', "%$search%")
            ->limit(10);

        if ($modifyQueryUsing) {
            $this->evaluate($modifyQueryUsing, ['query' => $query]);
        }

        return $query
            ->get(['id', $titleColumn])
            ->map(fn ($model) => [
                'label' => $model->{$titleColumn},
                'value' => $model->id,
            ])
            ->toArray();
    }

    public function preloadRelationship(string $relationshipName, string $titleColumn, ?Closure $modifyQueryUsing): array
    {
        if ($this->preloadLimit <= 0) {
            return [];
        }

        $relationshipQuery = $this->getRelationshipQuery($relationshipName);

        if (! $relationshipQuery) {
            return [];
        }

        $query = $relationshipQuery
            ->limit($this->preloadLimit);

        if ($modifyQueryUsing) {
            $this->evaluate($modifyQueryUsing, ['query' => $query]);
        }

        return $query
            ->get(['id', $titleColumn])
            ->mapWithKeys(fn ($model) => [
                $model->id => $model->{$titleColumn},
            ])
            ->toArray();
    }

    public function saveRelationshipUsing(Model $record, string $relationshipName, mixed $state, array $data): void
    {
        $relationship = $this->getRelationship($relationshipName);

        if (! $relationship) {
            return;
        }

        if (method_exists($relationship, 'sync')) {
            // Many-to-Many or Has-Many-Through
            $relationship->sync((array) $state);
        } elseif (method_exists($relationship, 'associate')) {
            // Belongs-To
            $relationship->associate($state);
            $record->save();
        } elseif (method_exists($relationship, 'dissociate') && $state === null) {
            // Belongs-To with null state
            $relationship->dissociate();
            $record->save();
        }
    }

    protected function getRelationship(string $relationshipName): ?Relation
    {
        if (! $relationshipName) {
            return null;
        }

        /** @var Model|null $record */
        $record = $this->getRecord() ?? $this->getModel();

        if (! $record || ! $record->isRelation($relationshipName)) {
            return null;
        }

        return (new RelationResolver)->resolveRelation($record, $relationshipName);
    }

    protected function getRelationshipQuery(string $relationshipName): ?Builder
    {
        if (! $relationshipName) {
            return null;
        }

        $relationship = Relation::noConstraints(fn () => $this->getRelationship($relationshipName));

        if (! $relationship) {
            return null;
        }

        return $relationship?->getQuery();
    }
}
