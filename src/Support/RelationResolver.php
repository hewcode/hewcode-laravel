<?php

namespace Hewcode\Hewcode\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use RuntimeException;

class RelationResolver
{
    public function resolveRelation(Model $record, string $relationName): Relation
    {
        if (method_exists($record, $relationName)) {
            return $record->{$relationName}();
        }

        if ($relationResolver = $record->relationResolver($record::class, $relationName)) {
            return $relationResolver($record);
        }

        throw new RuntimeException("Relation '$relationName' not found on model ".$record::class);
    }
}
