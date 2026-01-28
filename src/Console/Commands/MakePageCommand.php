<?php

namespace Hewcode\Hewcode\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'hew:page')]
class MakePageCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'hew:page';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Hewcode page controller';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Page';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('form')) {
            return $this->resolveStubPath('/stubs/page.form.stub');
        }

        return $this->resolveStubPath('/stubs/page.stub');
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
        return $rootNamespace.'\\Hewcode\\Controllers';
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

        $stub = $this->replaceView($stub);
        $stub = $this->replaceIcon($stub);
        $stub = $this->replacePanels($stub);
        $stub = $this->replaceNavigation($stub);

        if ($this->option('form')) {
            $stub = $this->replaceFormClass($stub);
        }

        return $stub;
    }

    /**
     * Replace the view for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceView($stub)
    {
        $view = $this->option('view');
        
        if (!$view) {
            // Infer view name from controller name (e.g., DashboardController -> dashboard)
            $controllerName = class_basename($this->getNameInput());
            $view = Str::kebab(str_replace('Controller', '', $controllerName));
        }

        return str_replace(['{{ view }}', '{{view}}'], $view, $stub);
    }

    /**
     * Replace the icon for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceIcon($stub)
    {
        $icon = $this->option('icon') ?: 'lucide-file-text';

        return str_replace(['{{ icon }}', '{{icon}}'], $icon, $stub);
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
                $panelsArray = array_map(fn($panel) => "'{$panel}'", explode(',', $panels));
                $panelsCode = 'return [' . implode(', ', $panelsArray) . '];';
            } else {
                $panelsCode = "return ['app'];";
            }
            $stub = str_replace(['{{ panels }}', '{{panels}}'], $panelsCode, $stub);
        }

        return $stub;
    }

    /**
     * Replace the navigation settings for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceNavigation($stub)
    {
        $noNav = $this->option('no-nav');
        $navigationProperty = $noNav ? 'protected bool $shouldRegisterNavigation = false;' : '// protected bool $shouldRegisterNavigation = true; // Default';

        return str_replace(['{{ navigationProperty }}', '{{navigationProperty}}'], $navigationProperty, $stub);
    }

    /**
     * Replace the form class for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceFormClass($stub)
    {
        $formClass = $this->option('form');

        if (!$formClass || $formClass === true) {
            // Try to infer from controller name
            // e.g., CustomFormController -> CustomForm
            $controllerName = class_basename($this->getNameInput());
            $formClass = str_replace('Controller', '', $controllerName);
        }

        return str_replace(['{{ form }}', '{{form}}'], $formClass, $stub);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['view', null, InputOption::VALUE_OPTIONAL, 'The view name for this page controller'],
            ['icon', 'i', InputOption::VALUE_OPTIONAL, 'The icon for navigation (lucide icon name)'],
            ['panels', 'p', InputOption::VALUE_OPTIONAL, 'Comma-separated list of panels, or "all" for all panels'],
            ['no-nav', null, InputOption::VALUE_NONE, 'Exclude this page from navigation'],
            ['form', null, InputOption::VALUE_OPTIONAL, 'Create a form controller with the specified form class name'],
        ];
    }
}