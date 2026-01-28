<?php

namespace Hewcode\Hewcode\Lists\Drivers;

use Hewcode\Hewcode\Lists\Tabs\Tab;
use Illuminate\Support\Collection;
use Hewcode\Hewcode\Lists\Filters\Filter;

class IterableDriver implements ListingDriver
{
    protected Collection $data;
    protected Collection $originalData;

    public function __construct(iterable $data)
    {
        $this->originalData = collect($data);
        $this->data = collect($data);
    }

    public function applySearch(string $searchTerm, array $searchableFields): void
    {
        if (empty($searchableFields)) {
            return;
        }

        $this->data = $this->data->filter(function ($item) use ($searchTerm, $searchableFields) {
            foreach ($searchableFields as $field) {
                $value = data_get($item, $field);

                if (is_string($value) && str_contains(strtolower($value), strtolower($searchTerm))) {
                    return true;
                }
            }

            return false;
        });
    }

    public function applyFilters(array $filters): void
    {
        foreach ($filters as $filter) {
            $this->applyFilterToQuery($filter);
        }
    }

    public function applyFilterToQuery(Filter $filter): Collection
    {
        if ($filter->filterUsing) {
            $this->data = $filter->filterUsing->__invoke($this->data);
            return $this->data;
        }

        if (!$filter->filled()) {
            return $this->data;
        }

        $value = $filter->getState();
        $filter->validate();

        $this->data = $filter->modifyCollection($this->data, $value);

        return $this->data;
    }

    public function applySort(?string $sortField, ?string $sortDirection, array $sortableFields, ?array $defaultSort, ?string $reorderable): void
    {
        if (!$sortField || (! array_key_exists($sortField, $sortableFields) && $defaultSort !== [$sortField, $sortDirection] && $reorderable !== $sortField)) {
            return;
        }

        $ascending = $sortDirection !== 'desc';

        $this->data = $this->data->sortBy(function ($item) use ($sortField) {
            return data_get($item, $sortField);
        }, SORT_REGULAR, !$ascending);
    }

    public function applyTab(Tab $tab): void
    {
        if ($tab->query) {
            $this->data = $tab->query->__invoke($this->data);
        }
    }

    public function paginate(int $perPage, string $pageName = 'page'): array
    {
        $currentPage = (int) request()->input($pageName, 1);
        $total = $this->data->count();
        $totalPages = (int) ceil($total / $perPage);

        $records = $this->data
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage);

        return [
            'records' => $records,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $total,
                'itemsPerPage' => $perPage,
            ],
        ];
    }

    public function getRecords(array $columns): array
    {
        return $this->data->all();
    }

    public function reorder(int $recordId, int $newPosition, string $reorderableColumn): bool
    {
        // Reordering is not supported for iterable data sources
        // as they don't persist changes
        return false;
    }
}
