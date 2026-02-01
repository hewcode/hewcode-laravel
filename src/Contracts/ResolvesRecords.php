<?php

namespace Hewcode\Hewcode\Contracts;

use Illuminate\Support\Collection;

interface ResolvesRecords
{
    public function resolveRecord(int|string $id): mixed;

    public function resolveRecords(array $ids): Collection;
}
