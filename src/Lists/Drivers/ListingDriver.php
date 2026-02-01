<?php

namespace Hewcode\Hewcode\Lists\Drivers;

use Hewcode\Hewcode\Lists\Filters\Filter;
use Hewcode\Hewcode\Lists\Schema\Column;
use Hewcode\Hewcode\Lists\Tabs\Tab;

interface ListingDriver
{
    /**
     * Apply search filtering to the data source.
     *
     * @param  string  $searchTerm  The search term to filter by
     * @param  array  $searchableFields  Array of field names that can be searched
     */
    public function applySearch(string $searchTerm, array $searchableFields): void;

    /**
     * Apply filters to the data source.
     *
     * @param  Filter[]  $filters  Array of Filter instances to apply
     */
    public function applyFilters(array $filters): void;

    /**
     * Apply the active tab to the data source.
     */
    public function applyTab(Tab $tab): void;

    /**
     * Apply sorting to the data source.
     *
     * @param  string|null  $sortField  The field to sort by
     * @param  string|null  $sortDirection  The sort direction ('asc' or 'desc')
     * @param  array  $sortableFields  Array of sortable fields with labels
     */
    public function applySort(?string $sortField, ?string $sortDirection, array $sortableFields, ?array $defaultSort, ?string $reorderable): void;

    /**
     * Paginate the data and return records with pagination metadata.
     *
     * @param  int  $perPage  Number of items per page
     * @param  string  $pageName  The query parameter name for the page number
     * @return array Array containing 'records' and 'pagination' keys
     */
    public function paginate(int $perPage, string $pageName = 'page'): array;

    /**
     * Get all records without pagination (used for non-paginated results).
     *
     * @param  Column[]  $columns  Array of Column instances for context
     * @return array Array of all records
     */
    public function getRecords(array $columns): array;

    /**
     * Reorder a record by updating its order position.
     *
     * @param  int  $recordId  The ID of the record to reorder
     * @param  int  $newPosition  The new position for the record
     * @param  string  $reorderableColumn  The column name used for ordering
     * @return bool True if reorder was successful, false otherwise
     */
    public function reorder(int $recordId, int $newPosition, string $reorderableColumn): bool;
}
