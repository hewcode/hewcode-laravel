<?php

namespace Hewcode\Hewcode\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait ResolvesRecords
{
    use HasModel;

    protected ?Closure $resolveRecordUsing = null;

    protected function resolve(int|string|array $ids): mixed
    {
        $isArray = is_array($ids);

        if ($this->resolveRecordUsing) {
            if ($isArray) {
                return collect($ids)->map(fn ($id) => ($this->resolveRecordUsing)($id));
            } else {
                return ($this->resolveRecordUsing)($ids);
            }
        } elseif ($this->model instanceof Model) {
            if ($isArray) {
                return $this->model::query()->whereIn('id', $ids)->get();
            } else {
                return $this->model::query()->find($ids);
            }
        } elseif ($this->model && method_exists($this->model::class, $isArray ? 'whereIn' : 'find')) {
            if ($isArray) {
                return $this->model::whereIn('id', $ids)->get();
            } else {
                return $this->model::find($ids);
            }
        } else {
            $type = $isArray ? 'records' : 'record';

            throw new InvalidArgumentException("Could not resolve $type. Please provide a valid eloquent model or a custom resolver using resolveRecordUsing().");
        }
    }

    public function resolveRecord(int|string $id): mixed
    {
        return $this->resolve($id);
    }

    public function resolveRecords(array $ids): Collection
    {
        return $this->resolve($ids);
    }

    public function resolveRecordUsing(Closure $callback): static
    {
        $this->resolveRecordUsing = $callback;

        return $this;
    }
}
