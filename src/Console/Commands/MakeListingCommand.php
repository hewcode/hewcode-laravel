<?php

namespace Hewcode\Hewcode\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'hew:listing')]
class MakeListingCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'hew:listing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Hewcode listing definition class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Listing';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/listing.stub');
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
        $stub = $this->replaceForm($stub);

        if ($this->option('generate')) {
            $stub = $this->generateListingSchema($stub);
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
        
        if (!$model) {
            // Infer model name from listing name (e.g., PostListing -> Post)
            $listingName = class_basename($this->getNameInput());
            $model = str_replace('Listing', '', $listingName);
        }

        $modelClass = class_basename($model);
        $modelNamespace = "App\\Models\\{$modelClass}";

        $stub = str_replace(['{{ model }}', '{{model}}'], $modelClass, $stub);
        $stub = str_replace(['{{ modelNamespace }}', '{{modelNamespace}}'], $modelNamespace, $stub);

        return $stub;
    }

    /**
     * Replace the form for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceForm($stub)
    {
        $form = $this->option('form');
        
        if ($form) {
            $formClass = class_basename($form);
            $formProperty = "protected ?string \$form = {$formClass}::class;";
        } else {
            $formProperty = '// protected ?string $form = null;';
        }

        return str_replace(['{{ formProperty }}', '{{formProperty}}'], $formProperty, $stub);
    }

    /**
     * Generate listing schema based on model table columns.
     *
     * @param  string  $stub
     * @return string
     */
    protected function generateListingSchema($stub)
    {
        $model = $this->option('model');
        
        if (!$model) {
            $listingName = class_basename($this->getNameInput());
            $model = str_replace('Listing', '', $listingName);
        }

        $modelClass = class_basename($model);
        $modelNamespace = "App\\Models\\{$modelClass}";

        try {
            // Check if the model class exists
            if (!class_exists($modelNamespace)) {
                $this->components->warn("Model {$modelNamespace} not found. Using default schema.");
                return $stub;
            }

            $modelInstance = new $modelNamespace;
            $tableName = $modelInstance->getTable();

            // Check if table exists
            if (!\Schema::hasTable($tableName)) {
                $this->components->warn("Table '{$tableName}' not found. Using default schema.");
                return $stub;
            }

            $columns = \Schema::getColumnListing($tableName);
            $columnTypes = [];
            
            foreach ($columns as $column) {
                $columnTypes[$column] = \Schema::getColumnType($tableName, $column);
            }

            // Generate listing schema
            $listingSchema = $this->generateColumnsSchema($columns, $columnTypes);
            $stub = str_replace('                Lists\Schema\TextColumn::make(\'id\')
                    ->sortable(),
                Lists\Schema\TextColumn::make(\'name\')
                    ->sortable()
                    ->searchable(),
                Lists\Schema\TextColumn::make(\'created_at\')
                    ->datetime()
                    ->sortable(),', $listingSchema, $stub);

        } catch (\Exception $e) {
            $this->components->warn("Could not generate schema: {$e->getMessage()}");
        }

        return $stub;
    }

    /**
     * Generate listing columns based on columns.
     *
     * @param  array  $columns
     * @param  array  $columnTypes
     * @return string
     */
    protected function generateColumnsSchema($columns, $columnTypes)
    {
        $schema = [];
        $excludeColumns = ['password', 'remember_token'];
        $priorityColumns = ['id', 'name', 'title', 'email'];
        
        // Add priority columns first
        foreach ($priorityColumns as $column) {
            if (in_array($column, $columns) && !in_array($column, $excludeColumns)) {
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
        $sortable = !str_contains($type, 'text') ? "\n{$indent}    ->sortable()" : '';
        
        return match (true) {
            str_contains($column, 'email') => $indent . "Lists\\Schema\\TextColumn::make('{$column}'){$sortable}{$searchable},",
            str_contains($type, 'boolean') || str_contains($column, 'is_') => $indent . "Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->boolean(){$sortable},",
            str_contains($type, 'date') && str_contains($column, 'time') => $indent . "Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->datetime(){$sortable},",
            str_contains($type, 'date') => $indent . "Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->date(){$sortable},",
            str_contains($type, 'time') => $indent . "Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->time(){$sortable},",
            str_ends_with($column, '_id') => $indent . "Lists\\Schema\\TextColumn::make('" . Str::before($column, '_id') . ".name'){$sortable}{$searchable},",
            str_contains($type, 'text') => $indent . "Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->limit(50)\n{$indent}    ->wrap(){$searchable},",
            in_array($column, ['status', 'type', 'category']) => $indent . "Lists\\Schema\\TextColumn::make('{$column}')\n{$indent}    ->badge(){$sortable},",
            default => $indent . "Lists\\Schema\\TextColumn::make('{$column}'){$sortable}{$searchable},"
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
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the listing applies to'],
            ['form', 'f', InputOption::VALUE_OPTIONAL, 'The form definition class to associate with this listing'],
            ['generate', 'g', InputOption::VALUE_NONE, 'Generate listing columns based on model table columns'],
        ];
    }
}