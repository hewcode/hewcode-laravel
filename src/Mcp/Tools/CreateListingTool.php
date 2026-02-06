<?php

namespace Hewcode\Hewcode\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateListingTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Creates a Hewcode Listing definition class with columns, filters, actions, and other configurations. Use this to scaffold data table views for Eloquent models.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'model' => 'required|string',
            'columns' => 'array',
            'columns.*.name' => 'required|string',
            'columns.*.type' => 'string|in:text,image,badge',
            'columns.*.sortable' => 'boolean',
            'columns.*.searchable' => 'boolean',
            'filters' => 'array',
            'actions' => 'array',
            'relationships' => 'array',
            'generate' => 'boolean',
        ]);

        $name = $validated['name'];
        $model = $validated['model'];
        $columns = $validated['columns'] ?? [];
        $filters = $validated['filters'] ?? [];
        $actions = $validated['actions'] ?? [];
        $relationships = $validated['relationships'] ?? [];
        $generate = $validated['generate'] ?? false;

        // Ensure name ends with "Listing"
        if (! Str::endsWith($name, 'Listing')) {
            $name .= 'Listing';
        }

        // Build the class
        $namespace = 'App\\Hewcode\\Listings';
        $path = app_path('Hewcode/Listings/'.$name.'.php');

        // Check if file exists
        if (File::exists($path)) {
            return Response::error("Listing already exists at {$path}. Use a different name or delete the existing file.");
        }

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));

        // Generate the listing class
        $content = $this->generateListingClass(
            $namespace,
            $name,
            $model,
            $columns,
            $filters,
            $actions,
            $relationships,
            $generate
        );

        File::put($path, $content);

        return Response::text($this->successMessage($name, $model, $path));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The Listing class name (e.g., "UserListing" or "User"). Will automatically append "Listing" if not present.')
                ->required(),

            'model' => $schema->string()
                ->description('The Eloquent model class name (e.g., "User", "App\\Models\\Post")')
                ->required(),

            'columns' => $schema->array()
                ->description('Array of column definitions. Each column represents a field to display in the table.')
                ->items($schema->object()
                    ->properties([
                        'name' => $schema->string()
                            ->description('Column field name (e.g., "title", "user.name" for relationships)')
                            ->required(),
                        'type' => $schema->string()
                            ->enum(['text', 'image', 'badge'])
                            ->description('Column type: text (default), image (for avatars/thumbnails), or badge (for status/tags)'),
                        'sortable' => $schema->boolean()
                            ->description('Whether this column can be sorted')
                            ->default(false),
                        'searchable' => $schema->boolean()
                            ->description('Whether this column is searchable')
                            ->default(false),
                    ])
                ),

            'filters' => $schema->array()
                ->description('Array of filter names to add (e.g., ["status", "category"])')
                ->items($schema->string()),

            'actions' => $schema->array()
                ->description('Array of action names to include (e.g., ["edit", "delete", "restore"])')
                ->items($schema->string()),

            'relationships' => $schema->array()
                ->description('Array of relationship names to eager load (e.g., ["user", "category"])')
                ->items($schema->string()),

            'generate' => $schema->boolean()
                ->description('Auto-generate columns from model\'s database table schema')
                ->default(false),
        ];
    }

    protected function generateListingClass(
        string $namespace,
        string $name,
        string $model,
        array $columns,
        array $filters,
        array $actions,
        array $relationships,
        bool $generate
    ): string {
        $modelShortName = class_basename($model);
        $modelClass = $model;

        if (! Str::contains($model, '\\')) {
            $modelClass = "App\\Models\\{$model}";
        }

        $uses = collect([
            'Hewcode\Hewcode\Actions',
            'Hewcode\Hewcode\Lists',
            $modelClass,
        ])->unique()->sort()->values();

        $usesString = $uses->map(fn ($use) => "use {$use};")->implode("\n");

        $columnsCode = $this->generateColumnsCode($columns, $generate);
        $filtersCode = $this->generateFiltersCode($filters);
        $actionsCode = $this->generateActionsCode($actions);
        $relationshipsString = $this->generateRelationshipsString($relationships);

        return <<<PHP
<?php

namespace {$namespace};

{$usesString}

class {$name} extends Lists\ListingDefinition
{
    protected string \$model = {$modelShortName}::class;

    public function default(Lists\Listing \$listing): Lists\Listing
    {
        return \$listing
            ->visible(auth()->check())
            ->query({$modelShortName}::query(){$relationshipsString})
            ->columns([
{$columnsCode}
            ])
{$actionsCode}{$filtersCode}
            ->defaultSort('created_at', 'desc')
            ->perPage(15);
    }
}

PHP;
    }

    protected function generateColumnsCode(array $columns, bool $generate): string
    {
        if (empty($columns) && ! $generate) {
            // Minimal default columns
            return <<<'CODE'
                Lists\Schema\TextColumn::make('id')
                    ->sortable(),
                Lists\Schema\TextColumn::make('created_at')
                    ->datetime()
                    ->sortable(),
CODE;
        }

        $code = [];

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $column['type'] ?? 'text';
            $sortable = $column['sortable'] ?? false;
            $searchable = $column['searchable'] ?? false;

            $columnClass = match ($type) {
                'image' => 'ImageColumn',
                'badge' => 'TextColumn', // Text column with badge modifier
                default => 'TextColumn',
            };

            $columnCode = "Lists\Schema\\{$columnClass}::make('{$name}')";

            $modifiers = [];
            if ($sortable) {
                $modifiers[] = '->sortable()';
            }
            if ($searchable) {
                $modifiers[] = '->searchable()';
            }
            if ($type === 'badge') {
                $modifiers[] = '->badge()';
            }
            if ($type === 'image') {
                $modifiers[] = '->size(48)';
            }

            if (! empty($modifiers)) {
                $columnCode .= "\n                    ".implode("\n                    ", $modifiers);
            }

            $code[] = "                {$columnCode},";
        }

        return implode("\n", $code);
    }

    protected function generateFiltersCode(array $filters): string
    {
        if (empty($filters)) {
            return '';
        }

        $code = ['            ->filters(['];

        foreach ($filters as $filter) {
            $code[] = "                Lists\Filters\SelectFilter::make('{$filter}'),";
        }

        $code[] = '            ])';

        return "\n".implode("\n", $code);
    }

    protected function generateActionsCode(array $actions): string
    {
        if (empty($actions)) {
            return '';
        }

        $code = ['            ->actions(['];

        foreach ($actions as $action) {
            $actionClass = match (strtolower($action)) {
                'edit' => 'Actions\Eloquent\EditAction::make()',
                'delete' => 'Actions\Eloquent\DeleteAction::make()',
                'restore' => 'Actions\Eloquent\RestoreAction::make()',
                default => "Actions\Action::make('{$action}')",
            };

            $code[] = "                {$actionClass},";
        }

        $code[] = '            ])';

        return "\n".implode("\n", $code);
    }

    protected function generateRelationshipsString(array $relationships): string
    {
        if (empty($relationships)) {
            return '';
        }

        $quoted = array_map(fn ($rel) => "'{$rel}'", $relationships);

        return '->with(['.implode(', ', $quoted).'])';
    }

    protected function successMessage(string $name, string $model, string $path): string
    {
        $relativePath = str_replace(base_path().'/', '', $path);

        return <<<EOT
✓ Created {$name} at {$relativePath}

Next steps:
1. Use in your controller:

   use App\Hewcode\Listings\\{$name};

   #[Lists\Expose]
   public function index(): Lists\Listing
   {
       return {$name}::make();
   }

2. Register the controller method in your Inertia page props:

   Props\Props::for(\$this)->components(['index'])

3. Use the Listing component in your frontend:

   import Listing from '@hewcode/react/components/data-table/Listing';

   const { index } = usePage().props;
   return <Listing {...index} />;

4. Customize columns, filters, and actions in {$relativePath}

Note: Labels will auto-generate from locale files (app.{model}.columns.field_name).
Remember to set proper visibility/authorization checks if needed.
EOT;
    }
}
