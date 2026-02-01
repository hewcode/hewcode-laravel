<?php

namespace Hewcode\Hewcode\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'hew:resource-page')]
class MakeResourcePageCommand extends GeneratorCommand
{
    protected $name = 'hew:resource-page';

    protected $description = 'Create a new Hewcode resource page controller';

    protected $type = 'Resource Page';

    protected function getStub()
    {
        if ($this->option('index')) {
            return $this->resolveStubPath('/stubs/resource-page.index.stub');
        }

        if ($this->option('edit')) {
            return $this->resolveStubPath('/stubs/resource-page.edit.stub');
        }

        if ($this->option('create')) {
            return $this->resolveStubPath('/stubs/resource-page.create.stub');
        }

        // Default to index if no option specified
        return $this->resolveStubPath('/stubs/resource-page.index.stub');
    }

    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\\Hewcode\\Controllers';
    }

    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $stub = $this->replaceNamespace($stub, $name)
            ->replaceClass($stub, $name);

        $stub = $this->replaceDefinitionClass($stub);
        $stub = $this->replaceIcon($stub);
        $stub = $this->replacePanels($stub);

        return $stub;
    }

    protected function replaceDefinitionClass($stub)
    {
        $definitionClass = $this->option('definition');

        if (! $definitionClass) {
            // Try to infer from controller name
            // e.g., ListUsersController -> UserListing
            // e.g., EditUserController -> UserForm
            $controllerName = class_basename($this->getNameInput());

            if ($this->option('index')) {
                $definitionClass = str_replace(['List', 'Controller'], ['', 'Listing'], $controllerName);
            } else {
                $definitionClass = str_replace(['Edit', 'Create', 'Controller'], ['', '', 'Form'], $controllerName);
            }
        }

        // Replace {{ listing }} or {{ form }} placeholder
        $stub = str_replace(['{{ listing }}', '{{listing}}'], $definitionClass, $stub);
        $stub = str_replace(['{{ form }}', '{{form}}'], $definitionClass, $stub);

        return $stub;
    }

    protected function replaceIcon($stub)
    {
        $icon = $this->option('icon') ?: 'lucide-file-text';

        return str_replace(['{{ icon }}', '{{icon}}'], $icon, $stub);
    }

    protected function replacePanels($stub)
    {
        $panels = $this->option('panels');

        if ($panels === 'all') {
            $panelsCode = 'public function panels(): array|true
    {
        return true;
    }';
            $stub = str_replace('    public function panels(): array
    {
        {{ panels }}
    }', $panelsCode, $stub);
        } else {
            if ($panels) {
                $panelsArray = array_map(fn ($panel) => "'{$panel}'", explode(',', $panels));
                $panelsCode = 'return ['.implode(', ', $panelsArray).'];';
            } else {
                $panelsCode = "return ['app'];";
            }
            $stub = str_replace(['{{ panels }}', '{{panels}}'], $panelsCode, $stub);
        }

        return $stub;
    }

    protected function getOptions()
    {
        return [
            ['index', null, InputOption::VALUE_NONE, 'Create an index (listing) page controller'],
            ['edit', null, InputOption::VALUE_NONE, 'Create an edit page controller'],
            ['create', null, InputOption::VALUE_NONE, 'Create a create page controller'],
            ['definition', 'd', InputOption::VALUE_OPTIONAL, 'The listing or form definition class name'],
            ['icon', 'i', InputOption::VALUE_OPTIONAL, 'The icon for navigation (lucide icon name)'],
            ['panels', 'p', InputOption::VALUE_OPTIONAL, 'Comma-separated list of panels, or "all" for all panels'],
        ];
    }
}
