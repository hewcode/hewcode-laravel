<?php

namespace Hewcode\Hewcode\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateResourceTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Creates a complete Hewcode Resource with listing and form methods. This generates a full CRUD interface including index, create, and edit pages. Optionally creates separate ListingDefinition and FormDefinition classes for reusability.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'model' => 'required|string',
            'panels' => 'array',
            'panels.*' => 'string',
            'listing_config' => 'array',
            'form_config' => 'array',
            'generate_definitions' => 'boolean',
            'generate' => 'boolean',
        ]);

        $name = $validated['name'];
        $model = $validated['model'];
        $panels = $validated['panels'] ?? ['admin'];
        $listingConfig = $validated['listing_config'] ?? [];
        $formConfig = $validated['form_config'] ?? [];
        $generateDefinitions = $validated['generate_definitions'] ?? false;
        $generate = $validated['generate'] ?? false;

        // Ensure name ends with "Resource"
        if (! Str::endsWith($name, 'Resource')) {
            $name .= 'Resource';
        }

        $namespace = 'App\\Hewcode\\Resources';
        $path = app_path('Hewcode/Resources/'.$name.'.php');

        // Check if file exists
        if (File::exists($path)) {
            return Response::error("Resource already exists at {$path}. Use a different name or delete the existing file.");
        }

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));

        $createdFiles = [];

        // Optionally generate separate definitions
        $listingClass = null;
        $formClass = null;

        if ($generateDefinitions) {
            // Generate ListingDefinition
            $listingName = str_replace('Resource', 'Listing', $name);
            $listingTool = new CreateListingTool;

            try {
                $listingResponse = $listingTool->handle(new Request([
                    'name' => $listingName,
                    'model' => $model,
                    'columns' => $listingConfig['columns'] ?? [],
                    'filters' => $listingConfig['filters'] ?? [],
                    'actions' => $listingConfig['actions'] ?? [],
                    'relationships' => $listingConfig['relationships'] ?? [],
                    'generate' => $generate,
                ]));

                $listingClass = "App\\Hewcode\\Listings\\{$listingName}";
                $createdFiles[] = "app/Hewcode/Listings/{$listingName}.php";
            } catch (\Exception $e) {
                return Response::error('Failed to generate listing: '.$e->getMessage());
            }

            // Generate FormDefinition
            $formName = str_replace('Resource', 'Form', $name);
            $formTool = new CreateFormTool;

            try {
                $formResponse = $formTool->handle(new Request([
                    'name' => $formName,
                    'model' => $model,
                    'fields' => $formConfig['fields'] ?? [],
                    'generate' => $generate,
                ]));

                $formClass = "App\\Hewcode\\Forms\\{$formName}";
                $createdFiles[] = "app/Hewcode/Forms/{$formName}.php";
            } catch (\Exception $e) {
                return Response::error('Failed to generate form: '.$e->getMessage());
            }
        }

        // Generate the resource class
        $content = $this->generateResourceClass(
            $namespace,
            $name,
            $model,
            $panels,
            $listingConfig,
            $formConfig,
            $listingClass,
            $formClass,
            $generate
        );

        File::put($path, $content);
        $createdFiles[] = str_replace(base_path().'/', '', $path);

        return Response::text($this->successMessage($name, $model, $createdFiles, $generateDefinitions));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The Resource class name (e.g., "ProductResource" or "Product"). Will automatically append "Resource" if not present.')
                ->required(),

            'model' => $schema->string()
                ->description('The Eloquent model class name (e.g., "Product", "App\\Models\\Post")')
                ->required(),

            'panels' => $schema->array()
                ->description('Array of panel names to assign this resource to (e.g., ["admin", "app"]). Defaults to ["admin"].')
                ->items($schema->string())
                ->default(['admin']),

            'listing_config' => $schema->object()
                ->description('Configuration for the listing (data table). Same parameters as create_listing tool.')
                ->properties([
                    'columns' => $schema->array()
                        ->description('Array of column definitions')
                        ->items($schema->object()),
                    'filters' => $schema->array()
                        ->description('Array of filter names')
                        ->items($schema->string()),
                    'actions' => $schema->array()
                        ->description('Array of action names (e.g., ["edit", "delete"])')
                        ->items($schema->string()),
                    'relationships' => $schema->array()
                        ->description('Array of relationships to eager load')
                        ->items($schema->string()),
                ]),

            'form_config' => $schema->object()
                ->description('Configuration for the form. Same parameters as create_form tool.')
                ->properties([
                    'fields' => $schema->array()
                        ->description('Array of field definitions')
                        ->items($schema->object()),
                ]),

            'generate_definitions' => $schema->boolean()
                ->description('Whether to generate separate ListingDefinition and FormDefinition classes for reusability. If false, listing and form are defined inline in the resource.')
                ->default(false),

            'generate' => $schema->boolean()
                ->description('Auto-generate columns and fields from model\'s database table schema')
                ->default(false),
        ];
    }

    protected function generateResourceClass(
        string $namespace,
        string $name,
        string $model,
        array $panels,
        array $listingConfig,
        array $formConfig,
        ?string $listingClass,
        ?string $formClass,
        bool $generate
    ): string {
        $modelShortName = class_basename($model);
        $modelClass = $model;

        if (! Str::contains($model, '\\')) {
            $modelClass = "App\\Models\\{$model}";
        }

        $uses = collect([
            'Hewcode\Hewcode\Actions',
            'Hewcode\Hewcode\Forms',
            'Hewcode\Hewcode\Lists',
            'Hewcode\Hewcode\Panel',
            $modelClass,
        ]);

        if ($listingClass) {
            $uses->push($listingClass);
        }
        if ($formClass) {
            $uses->push($formClass);
        }

        $uses = $uses->unique()->sort()->values();
        $usesString = $uses->map(fn ($use) => "use {$use};")->implode("\n");

        $panelsArray = collect($panels)->map(fn ($panel) => "'{$panel}'")->implode(', ');

        // Generate listing and form methods
        if ($listingClass && $formClass) {
            $listingShortName = class_basename($listingClass);
            $formShortName = class_basename($formClass);

            $listingMethod = <<<PHP

    public function listing(Lists\Listing \$listing): Lists\Listing
    {
        return {$listingShortName}::make('listing');
    }
PHP;

            $formMethod = <<<PHP

    public function form(Forms\Form \$form): Forms\Form
    {
        return {$formShortName}::make();
    }
PHP;
        } else {
            // Generate inline listing and form
            $listingMethod = $this->generateInlineListingMethod($modelShortName, $listingConfig, $generate);
            $formMethod = $this->generateInlineFormMethod($modelShortName, $formConfig, $generate);
        }

        return <<<PHP
<?php

namespace {$namespace};

{$usesString}

class {$name} extends Panel\Resource
{
    protected string \$model = {$modelShortName}::class;

    public function panels(): array
    {
        return [{$panelsArray}];
    }

    public function pages(): array
    {
        return [
            Panel\Controllers\Resources\IndexController::page(),
            Panel\Controllers\Resources\CreateController::page(),
            Panel\Controllers\Resources\EditController::page(),
        ];
    }
{$listingMethod}
{$formMethod}
}

PHP;
    }

    protected function generateInlineListingMethod(string $modelShortName, array $config, bool $generate): string
    {
        $columns = $config['columns'] ?? [];
        $filters = $config['filters'] ?? [];
        $actions = $config['actions'] ?? ['edit', 'delete'];
        $relationships = $config['relationships'] ?? [];

        $columnsCode = $this->generateColumnsCode($columns, $generate);
        $filtersCode = $this->generateFiltersCode($filters);
        $actionsCode = $this->generateActionsCode($actions);
        $relationshipsString = $this->generateRelationshipsString($relationships);

        return <<<PHP

    public function listing(Lists\Listing \$listing): Lists\Listing
    {
        return \$listing
            ->visible(auth()->check())
            ->query({$modelShortName}::query(){$relationshipsString})
            ->columns([
{$columnsCode}
            ])
{$actionsCode}{$filtersCode}
            ->defaultSort('created_at', 'desc');
    }
PHP;
    }

    protected function generateInlineFormMethod(string $modelShortName, array $config, bool $generate): string
    {
        $fields = $config['fields'] ?? [];
        $fieldsCode = $this->generateFieldsCode($fields, $generate);

        return <<<PHP

    public function form(Forms\Form \$form): Forms\Form
    {
        return \$form
            ->visible(auth()->check())
            ->schema([
{$fieldsCode}
            ]);
    }
PHP;
    }

    protected function generateColumnsCode(array $columns, bool $generate): string
    {
        if (empty($columns) && ! $generate) {
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

    protected function generateFieldsCode(array $fields, bool $generate): string
    {
        if (empty($fields) && ! $generate) {
            return <<<'CODE'
                Forms\Schema\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
CODE;
        }

        $code = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text_input';
            $validation = $field['validation'] ?? [];

            $fieldClass = match ($type) {
                'text_input' => 'TextInput',
                'textarea' => 'Textarea',
                'select' => 'Select',
                'date_time_picker' => 'DateTimePicker',
                'file_upload' => 'FileUpload',
                default => 'TextInput',
            };

            $fieldCode = "Forms\Schema\\{$fieldClass}::make('{$name}')";
            $modifiers = [];

            foreach ($validation as $rule) {
                if ($rule === 'required') {
                    $modifiers[] = '->required()';
                } elseif (Str::startsWith($rule, 'max:')) {
                    $max = Str::after($rule, 'max:');
                    $modifiers[] = "->maxLength({$max})";
                }
            }

            if (! empty($modifiers)) {
                $fieldCode .= "\n                    ".implode("\n                    ", $modifiers);
            }

            $code[] = "                {$fieldCode},";
        }

        return implode("\n", $code);
    }

    protected function successMessage(string $name, string $model, array $createdFiles, bool $generatedDefinitions): string
    {
        $filesSection = collect($createdFiles)->map(fn ($file) => "  - {$file}")->implode("\n");

        $definitionsNote = $generatedDefinitions
            ? "\nNote: Separate Listing and Form definitions were created for reusability."
            : "\nNote: Listing and form are defined inline. Use generate_definitions: true to create separate reusable classes.";

        return <<<EOT
✓ Created {$name} resource

Files created:
{$filesSection}
{$definitionsNote}

The resource includes:
- Index page (listing with data table)
- Create page (form for new records)
- Edit page (form for existing records)

Next steps:
1. The resource is automatically discovered and registered in your panel(s)

2. Access the resource at:
   /{panel-name}/{model-plural}
   Example: /admin/products

3. Navigation is auto-registered with the resource name

4. Customize the resource, listing, and form as needed

5. Run migrations if you haven't already:
   php artisan migrate

The resource is ready to use! Visit your panel to see it in action.
EOT;
    }
}
