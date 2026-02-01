<?php

namespace Hewcode\Hewcode\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'hew:form')]
class MakeFormCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'hew:form';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Hewcode form definition class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Form';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/form.stub');
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

        if ($this->option('generate')) {
            $stub = $this->generateFormSchema($stub);
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
            // Infer model name from form name (e.g., PostForm -> Post)
            $formName = class_basename($this->getNameInput());
            $model = str_replace('Form', '', $formName);
        }

        $modelClass = class_basename($model);
        $modelNamespace = "App\\Models\\{$modelClass}";

        $stub = str_replace(['{{ model }}', '{{model}}'], $modelClass, $stub);
        $stub = str_replace(['{{ modelNamespace }}', '{{modelNamespace}}'], $modelNamespace, $stub);

        return $stub;
    }

    /**
     * Generate form schema based on model table columns.
     *
     * @param  string  $stub
     * @return string
     */
    protected function generateFormSchema($stub)
    {
        $model = $this->option('model');

        if (! $model) {
            $formName = class_basename($this->getNameInput());
            $model = str_replace('Form', '', $formName);
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
            $formSchema = $this->generateFieldsSchema($columns, $columnTypes);
            $stub = str_replace('                Forms\Schema\TextInput::make(\'name\')
                    ->required(),
                // Add more form fields here', $formSchema, $stub);

        } catch (\Exception $e) {
            $this->components->warn("Could not generate schema: {$e->getMessage()}");
        }

        return $stub;
    }

    /**
     * Generate form fields based on columns.
     *
     * @param  array  $columns
     * @param  array  $columnTypes
     * @return string
     */
    protected function generateFieldsSchema($columns, $columnTypes)
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
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the form applies to'],
            ['generate', 'g', InputOption::VALUE_NONE, 'Generate form fields based on model table columns'],
        ];
    }
}
