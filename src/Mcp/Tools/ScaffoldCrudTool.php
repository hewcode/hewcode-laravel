<?php

namespace Hewcode\Hewcode\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ScaffoldCrudTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Scaffolds complete CRUD functionality for a model including migration, model class, and Hewcode resource with listing and form. This is the highest-level tool that creates everything needed for a working feature from scratch.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'model_name' => 'required|string',
            'table_fields' => 'array',
            'table_fields.*.name' => 'required|string',
            'table_fields.*.type' => 'required|string',
            'table_fields.*.nullable' => 'boolean',
            'table_fields.*.default' => 'string',
            'table_fields.*.unique' => 'boolean',
            'table_fields.*.index' => 'boolean',
            'panels' => 'array',
            'panels.*' => 'string',
            'generate_migration' => 'boolean',
            'generate_model' => 'boolean',
            'generate_resource' => 'boolean',
        ]);

        $modelName = $validated['model_name'];
        $tableFields = $validated['table_fields'] ?? [];
        $panels = $validated['panels'] ?? ['admin'];
        $generateMigration = $validated['generate_migration'] ?? true;
        $generateModel = $validated['generate_model'] ?? true;
        $generateResource = $validated['generate_resource'] ?? true;

        // Ensure model name is singular and PascalCase
        $modelName = Str::studly(Str::singular($modelName));
        $tableName = Str::snake(Str::plural($modelName));

        $createdFiles = [];
        $migrationPath = null;
        $modelPath = null;

        // Generate migration
        if ($generateMigration) {
            try {
                $migrationPath = $this->generateMigration($modelName, $tableName, $tableFields);
                $createdFiles[] = $migrationPath;
            } catch (\Exception $e) {
                return Response::error('Failed to generate migration: '.$e->getMessage());
            }
        }

        // Generate model
        if ($generateModel) {
            try {
                $modelPath = $this->generateModel($modelName, $tableName, $tableFields);
                $createdFiles[] = $modelPath;
            } catch (\Exception $e) {
                return Response::error('Failed to generate model: '.$e->getMessage());
            }
        }

        // Generate resource
        if ($generateResource) {
            try {
                $resourceTool = new CreateResourceTool;

                // Build listing and form configs from table fields
                $listingConfig = $this->buildListingConfig($tableFields);
                $formConfig = $this->buildFormConfig($tableFields);

                $resourceResponse = $resourceTool->handle(new Request([
                    'name' => "{$modelName}Resource",
                    'model' => $modelName,
                    'panels' => $panels,
                    'listing_config' => $listingConfig,
                    'form_config' => $formConfig,
                    'generate_definitions' => true,
                ]));

                $createdFiles[] = "app/Hewcode/Resources/{$modelName}Resource.php";
                $createdFiles[] = "app/Hewcode/Listings/{$modelName}Listing.php";
                $createdFiles[] = "app/Hewcode/Forms/{$modelName}Form.php";
            } catch (\Exception $e) {
                return Response::error('Failed to generate resource: '.$e->getMessage());
            }
        }

        return Response::text($this->successMessage($modelName, $tableName, $createdFiles, $panels));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'model_name' => $schema->string()
                ->description('The model name (singular, e.g., "Product", "BlogPost"). Will be converted to PascalCase.')
                ->required(),

            'table_fields' => $schema->array()
                ->description('Array of database field definitions for the migration')
                ->items($schema->object()
                    ->properties([
                        'name' => $schema->string()
                            ->description('Field name (e.g., "title", "price", "status")')
                            ->required(),
                        'type' => $schema->string()
                            ->description('Laravel migration column type (string, text, integer, decimal, boolean, date, datetime, json, etc.)')
                            ->required(),
                        'nullable' => $schema->boolean()
                            ->description('Whether the field can be null')
                            ->default(false),
                        'default' => $schema->string()
                            ->description('Default value for the field'),
                        'unique' => $schema->boolean()
                            ->description('Whether the field should be unique')
                            ->default(false),
                        'index' => $schema->boolean()
                            ->description('Whether to add an index on this field')
                            ->default(false),
                    ])
                )
                ->required(),

            'panels' => $schema->array()
                ->description('Array of panel names to assign the resource to (e.g., ["admin", "app"])')
                ->items($schema->string())
                ->default(['admin']),

            'generate_migration' => $schema->boolean()
                ->description('Whether to generate a migration file')
                ->default(true),

            'generate_model' => $schema->boolean()
                ->description('Whether to generate a model class')
                ->default(true),

            'generate_resource' => $schema->boolean()
                ->description('Whether to generate a Hewcode resource (listing + form)')
                ->default(true),
        ];
    }

    protected function generateMigration(string $modelName, string $tableName, array $fields): string
    {
        $migrationName = 'create_'.$tableName.'_table';
        $timestamp = date('Y_m_d_His');
        $className = 'Create'.Str::studly($tableName).'Table';

        $migrationPath = database_path("migrations/{$timestamp}_{$migrationName}.php");

        // Check if migration already exists
        $existingMigrations = glob(database_path('migrations/*_'.$migrationName.'.php'));
        if (! empty($existingMigrations)) {
            throw new \Exception('Migration already exists: '.basename($existingMigrations[0]));
        }

        $fieldsCode = $this->generateMigrationFields($fields);

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
{$fieldsCode}
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

PHP;

        File::put($migrationPath, $content);

        return str_replace(base_path().'/', '', $migrationPath);
    }

    protected function generateMigrationFields(array $fields): string
    {
        $code = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $nullable = $field['nullable'] ?? false;
            $default = $field['default'] ?? null;
            $unique = $field['unique'] ?? false;
            $index = $field['index'] ?? false;

            // Skip id and timestamps (handled separately)
            if (in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            // Build the column definition
            $line = "\$table->{$type}('{$name}')";

            if ($nullable) {
                $line .= '->nullable()';
            }

            if ($default !== null) {
                $line .= "->default('{$default}')";
            }

            if ($unique) {
                $line .= '->unique()';
            }

            if ($index) {
                $line .= '->index()';
            }

            $line .= ';';

            $code[] = "            {$line}";
        }

        return implode("\n", $code);
    }

    protected function generateModel(string $modelName, string $tableName, array $fields): string
    {
        $namespace = 'App\\Models';
        $modelPath = app_path("Models/{$modelName}.php");

        // Check if model already exists
        if (File::exists($modelPath)) {
            throw new \Exception("Model already exists: {$modelPath}");
        }

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($modelPath));

        // Build fillable array
        $fillable = collect($fields)
            ->pluck('name')
            ->filter(fn ($name) => ! in_array($name, ['id', 'created_at', 'updated_at']))
            ->map(fn ($name) => "'{$name}'")
            ->implode(', ');

        // Build casts array
        $casts = $this->generateModelCasts($fields);
        $castsCode = $casts ? "\n\n    protected array \$casts = [\n{$casts}\n    ];" : '';

        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Model;

class {$modelName} extends Model
{
    protected \$fillable = [{$fillable}];{$castsCode}
}

PHP;

        File::put($modelPath, $content);

        return str_replace(base_path().'/', '', $modelPath);
    }

    protected function generateModelCasts(array $fields): string
    {
        $casts = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];

            // Skip standard fields
            if (in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            // Map migration types to cast types
            $castType = match ($type) {
                'boolean' => 'boolean',
                'integer', 'bigInteger', 'unsignedBigInteger' => 'integer',
                'decimal', 'float', 'double' => 'decimal:2',
                'json' => 'array',
                'date' => 'date',
                'datetime', 'timestamp' => 'datetime',
                default => null,
            };

            if ($castType) {
                $casts[] = "        '{$name}' => '{$castType}',";
            }
        }

        return implode("\n", $casts);
    }

    protected function buildListingConfig(array $fields): array
    {
        $columns = [];
        $filters = [];

        // Always include ID
        $columns[] = [
            'name' => 'id',
            'sortable' => true,
        ];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];

            // Skip timestamps (we'll add created_at separately)
            if (in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            // Determine column configuration
            $column = ['name' => $name];

            // Text fields are searchable
            if (in_array($type, ['string', 'text'])) {
                $column['searchable'] = true;
                $column['sortable'] = true;
            }

            // Numeric fields are sortable
            if (in_array($type, ['integer', 'decimal', 'float', 'double'])) {
                $column['sortable'] = true;
            }

            // Boolean/enum-like fields as badges
            if (in_array($type, ['boolean', 'string']) && in_array($name, ['status', 'role', 'type'])) {
                $column['type'] = 'badge';
            }

            $columns[] = $column;

            // Add filters for status-like fields
            if (in_array($name, ['status', 'type', 'category', 'role'])) {
                $filters[] = $name;
            }
        }

        // Add created_at
        $columns[] = [
            'name' => 'created_at',
            'sortable' => true,
        ];

        return [
            'columns' => $columns,
            'filters' => $filters,
            'actions' => ['edit', 'delete'],
        ];
    }

    protected function buildFormConfig(array $fields): array
    {
        $formFields = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $nullable = $field['nullable'] ?? false;

            // Skip auto-generated fields
            if (in_array($name, ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            // Determine form field type and validation
            $formField = ['name' => $name];
            $validation = [];

            if (! $nullable) {
                $validation[] = 'required';
            }

            // Map database type to form field type
            $formField['type'] = match ($type) {
                'text' => 'textarea',
                'boolean' => 'select',
                'date' => 'date_time_picker',
                'datetime', 'timestamp' => 'date_time_picker',
                'json' => 'textarea',
                default => 'text_input',
            };

            // Add type-specific validation
            if ($type === 'string') {
                $validation[] = 'max:255';
            }

            if (in_array($type, ['integer', 'decimal', 'float', 'double'])) {
                $validation[] = 'numeric';
            }

            if ($type === 'string' && Str::contains($name, 'email')) {
                $validation[] = 'email';
            }

            $formField['validation'] = $validation;

            // Add options for specific field types
            if ($formField['type'] === 'textarea') {
                $formField['options'] = ['rows' => 5];
            }

            $formFields[] = $formField;
        }

        return ['fields' => $formFields];
    }

    protected function successMessage(string $modelName, string $tableName, array $createdFiles, array $panels): string
    {
        $filesSection = collect($createdFiles)->map(fn ($file) => "  ✓ {$file}")->implode("\n");
        $panelsString = implode(', ', $panels);

        return <<<EOT
🎉 Successfully scaffolded complete CRUD for {$modelName}!

Files created:
{$filesSection}

What was generated:
  • Migration: {$tableName} table with all specified fields
  • Model: {$modelName} with fillable fields and type casts
  • Resource: Complete CRUD interface with listing and form
  • Listing: Data table with sortable/searchable columns
  • Form: Input fields with validation rules

Next steps:

1. Run the migration:
   php artisan migrate

2. (Optional) Seed some test data:
   php artisan tinker
   >>> {$modelName}::factory(10)->create()

3. Access the resource in your panel:
   /{$panelsString}/{$tableName}

4. The resource is auto-registered in the panel navigation

5. Customize as needed:
   - Migration: Add indexes, foreign keys, etc.
   - Model: Add relationships, accessors, mutators
   - Listing: Customize columns, add filters
   - Form: Adjust fields, validation rules

Your {$modelName} CRUD is ready to use! 🚀
EOT;
    }
}
