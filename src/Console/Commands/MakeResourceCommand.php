<?php

namespace Hewcode\Hewcode\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'hew:resource')]
class MakeResourceCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'hew:resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Hewcode resource class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/resource.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\Hewcode\\Resources';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $stub = $this->replaceNamespace($stub, $name)
            ->replaceClass($stub, $name);

        $stub = $this->replaceModel($stub);
        $stub = $this->replacePanels($stub);

        if ($this->option('generate')) {
            $stub = $this->generateSchemas($stub);
        }

        return $stub;
    }

    /**
     * Replace the model for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceModel($stub)
    {
        $model = $this->option('model');

        if (! $model) {
            // Infer model name from resource name (e.g., PostsResource -> Post)
            $resourceName = class_basename($this->getNameInput());
            $model = Str::singular(str_replace('Resource', '', $resourceName));
        }

        $modelClass = class_basename($model);
        $modelNamespace = "App\\Models\\{$modelClass}";

        $stub = str_replace(['{{ model }}', '{{model}}'], $modelClass, $stub);
        $stub = str_replace(['{{ modelNamespace }}', '{{modelNamespace}}'], $modelNamespace, $stub);

        return $stub;
    }

    /**
     * Replace the panels for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replacePanels($stub)
    {
        $panels = $this->option('panels');

        if ($panels) {
            $panelsArray = array_map(fn ($panel) => "'{$panel}'", explode(',', $panels));
            $panelsCode = '['.implode(', ', $panelsArray).']';
            $panelsMethod = "    public function panels(): array\n    {\n        return {$panelsCode};\n    }\n\n";
        } else {
            $panelsMethod = '';
        }

        return str_replace(['{{ panelsMethod }}', '{{panelsMethod}}'], $panelsMethod, $stub);
    }

    /**
     * Generate form and listing schemas based on model table columns.
     *
     * @param  string  $stub
     * @return string
     */
    protected function generateSchemas($stub)
    {
        $model = $this->option('model');

        if (! $model) {
            $resourceName = class_basename($this->getNameInput());
            $model = Str::singular(str_replace('Resource', '', $resourceName));
        }

        $modelClass = class_basename($model);
        $modelNamespace = "App\\Models\\{$modelClass}";

        try {
            // Check if the model class exists
            if (! class_exists($modelNamespace)) {
                $this->components->warn("Model {$modelNamespace} not found. Using default schema.");

                return $stub;
            }

            $modelInstance = new $modelNamespace;
            $tableName = $modelInstance->getTable();

            // Check if table exists
            if (! \Schema::hasTable($tableName)) {
                $this->components->warn("Table '{$tableName}' not found. Using default schema.");

                return $stub;
            }

            $columns = \Schema::getColumnListing($tableName);
            $columnTypes = [];

            foreach ($columns as $column) {
                $columnTypes[$column] = \Schema::getColumnType($tableName, $column);
            }

            // Generate form schema
            $formSchema = $this->generateFormSchema($columns, $columnTypes);
            $stub = str_replace('                Forms\Schema\TextInput::make(\'name\')
                    ->required(),
                // Add more form fields here', $formSchema, $stub);

            // Generate listing schema
            $listingSchema = $this->generateListingSchema($columns, $columnTypes);
            $stub = str_replace('                Lists\Schema\TextColumn::make(\'id\')
                    ->sortable(),
                Lists\Schema\TextColumn::make(\'name\')
                    ->sortable()
                    ->searchable(),
                Lists\Schema\TextColumn::make(\'created_at\')
                    ->datetime()
                    ->sortable(),', $listingSchema, $stub);

        } catch (\Exception $e) {
            $this->components->warn("Could not generate schemas: {$e->getMessage()}");
        }

        return $stub;
    }

    /**
     * Generate form schema based on columns.
     *
     * @param  array  $columns
     * @param  array  $columnTypes
     * @return string
     */
    protected function generateFormSchema($columns, $columnTypes)
    {
        $schema = [];
        $excludeColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at'];

        foreach ($columns as $column) {
            if (in_array($column, $excludeColumns)) {
                continue;
            }

            $type = $columnTypes[$column];
            $field = $this->getFormFieldForType($column, $type);

            if ($field) {
                $schema[] = $field;
            }
        }

        return implode("\n", $schema);
    }

    /**
     * Generate listing schema based on columns.
     *
     * @param  array  $columns
     * @param  array  $columnTypes
     * @return string
     */
    protected function generateListingSchema($columns, $columnTypes)
    {
        $schema = [];
        $excludeColumns = ['password', 'remember_token'];
        $priorityColumns = ['id', 'name', 'title', 'email'];

        // Add priority columns first
        foreach ($priorityColumns as $column) {
            if (in_array($column, $columns) && ! in_array($column, $excludeColumns)) {
                $type = $columnTypes[$column];
                $field = $this->getListingColumnForType($column, $type);
                if ($field) {
                    $schema[] = $field;
                }
            }
        }

        // Add remaining columns
        foreach ($columns as $column) {
            if (in_array($column, $priorityColumns) || in_array($column, $excludeColumns)) {
                continue;
            }

            $type = $columnTypes[$column];
            $field = $this->getListingColumnForType($column, $type);

            if ($field) {
                $schema[] = $field;
            }
        }

        return implode("\n", $schema);
    }

    /**
     * Get form field for column type.
     *
     * @param  string  $column
     * @param  string  $type
     * @return string|null
     */
    protected function getFormFieldForType($column, $type)
    {
        $indent = '                ';

        return match (true) {
            str_contains($column, 'email') => $indent."Forms\\Schema\\TextInput::make('{$column}')\n{$indent}    ->email(),",
            str_contains($column, 'password') => $indent."Forms\\Schema\\TextInput::make('{$column}')\n{$indent}    ->password(),",
            str_contains($column, 'phone') => $indent."Forms\\Schema\\TextInput::make('{$column}')\n{$indent}    ->tel(),",
            str_contains($type, 'text') || str_contains($column, 'description') || str_contains($column, 'content') => $indent."Forms\\Schema\\Textarea::make('{$column}'),",
            str_contains($type, 'boolean') || str_contains($column, 'is_') => $indent."Forms\\Schema\\Toggle::make('{$column}'),",
            str_contains($type, 'date') && str_contains($column, 'time') => $indent."Forms\\Schema\\DateTimePicker::make('{$column}'),",
            str_contains($type, 'date') => $indent."Forms\\Schema\\DateTimePicker::make('{$column}')\n{$indent}    ->time(false),",
            str_contains($type, 'time') => $indent."Forms\\Schema\\DateTimePicker::make('{$column}')\n{$indent}    ->date(false),",
            str_contains($type, 'json') => $indent."Forms\\Schema\\KeyValue::make('{$column}'),",
            str_ends_with($column, '_id') => $indent."Forms\\Schema\\Select::make('{$column}')\n{$indent}    ->relationship('".Str::before($column, '_id')."'),",
            str_contains($type, 'integer') || str_contains($type, 'decimal') || str_contains($type, 'float') => $indent."Forms\\Schema\\TextInput::make('{$column}')\n{$indent}    ->numeric(),",
            default => $indent."Forms\\Schema\\TextInput::make('{$column}'),"
        };
    }

    /**
     * Get listing column for column type.
     *
     * @param  string  $column
     * @param  string  $type
     * @return string|null
     */
    protected function getListingColumnForType($column, $type)
    {
        $indent = '                ';
        $searchable = in_array($column, ['name', 'title', 'email', 'slug']) ? "\n{$indent}    ->searchable()" : '';
        $sortable = ! str_contains($type, 'text') ? "\n{$indent}    ->sortable()" : '';

        return match (true) {
            str_contains($column, 'email') => $indent."Lists\\Schema\\TextColumn::make('{$column}'){$sortable}{$searchable},",
            str_contains($type, 'boolean') || str_contains($column, 'is_') => $indent."Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->boolean(){$sortable},",
            str_contains($type, 'date') && str_contains($column, 'time') => $indent."Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->datetime(){$sortable},",
            str_contains($type, 'date') => $indent."Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->date(){$sortable},",
            str_contains($type, 'time') => $indent."Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->time(){$sortable},",
            str_ends_with($column, '_id') => $indent."Lists\\Schema\\TextColumn::make('".Str::before($column, '_id').".name'){$sortable}{$searchable},",
            str_contains($type, 'text') => $indent."Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->limit(50)\n{$indent}    ->wrap(){$searchable},",
            in_array($column, ['status', 'type', 'category']) => $indent."Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->badge(){$sortable},",
            default => $indent."Lists\\Schema\\TextColumn::make('{$column}'){$sortable}{$searchable},"
        };
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the resource applies to'],
            ['panels', 'p', InputOption::VALUE_OPTIONAL, 'Comma-separated list of panels this resource belongs to'],
            ['generate', 'g', InputOption::VALUE_NONE, 'Generate form and listing schemas based on model table columns'],
        ];
    }
}
