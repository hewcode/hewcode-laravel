<?php

namespace Hewcode\Hewcode\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface ResolvesRecords
{
    public function resolveRecord(int|string $id): mixed;

    public function resolveRecords(array $ids): Collection;
}
