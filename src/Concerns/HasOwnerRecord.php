<?php

namespace Hewcode\Hewcode\Concerns;

use Closure;
use Hewcode\Hewcode\Lists\Drivers\EloquentDriver;
use Hewcode\Hewcode\Support\RelationResolver;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

trait HasOwnerRecord
{
    protected mixed $ownerRecord = null;

    protected ?string $relationshipName = null;

    protected ?Closure $modifyRelationshipQueryUsing = null;

    public function ownerRecord(mixed $ownerRecord): static
    {
        $this->ownerRecord = $ownerRecord;

        return $this;
    }

    public function relationship(string $name, ?Closure $modifyRelationshipQueryUsing = null): static
    {
        $this->relationshipName = $name;
        $this->modifyRelationshipQueryUsing = $modifyRelationshipQueryUsing;

        $this->buildDriverUsing(function () {
            $query = $this->getRelationshipQuery();

            $this->driver = new EloquentDriver($query);

            if (! $this->model) {
                $this->model($query->getModel());
            }
        });

        return $this;
    }

    public function getOwnerRecord(): mixed
    {
        return $this->ownerRecord;
    }

    public function getRelationshipName(): ?string
    {
        return $this->relationshipName;
    }

    public function getRelationshipQuery(): Builder
    {
        $ownerRecord = $this->getOwnerRecord();

        if (! $ownerRecord) {
            throw new RuntimeException('Owner record must first be set using ownerRecord() before getting the relationship query.');
        }

        // @todo: assumes eloquent, use driver?
        $relation = (new RelationResolver)->resolveRelation($this->getOwnerRecord(), $this->getRelationshipName());

        $query = $relation->getQuery();

        if ($this->modifyRelationshipQueryUsing instanceof Closure) {
            ($this->modifyRelationshipQueryUsing)($query);
        }

        return $query;
    }
}
