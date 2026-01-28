<?php

namespace Hewcode\Hewcode;

use Hewcode\Hewcode\Panel\Controllers\PageController;
use Hewcode\Hewcode\Panel\Resource;
use Hewcode\Hewcode\Support\Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class Manager
{
    protected array $discoveryPaths = [
        './app/Hewcode/Controllers',
        './app/Hewcode/Resources',
        './vendor/hewcode/hewcode/src/Panel/Controllers',
    ];

    protected array $panels = [];

    public function __construct(
        //
    ) {}

    public function shareWithResponse(string $key, string|null $identifier, array $data): void
    {
        $current = session()->get("hewcode.$key", []);

        if ($identifier === null) {
            $current[] = $data;
        } else {
            $current[$identifier] = $data;
        }

        session()->put("hewcode.$key", $current);
    }

    public function getSharedData(string $key): array
    {
        $data = session()->get("hewcode.$key", []);
        session()->forget("hewcode.$key");

        return $data;
    }

    public function sharedData(): array
    {
        $data = [
            'toasts' => $this->getSharedData('toasts'),
            'actions' => $this->getSharedData('actions'),
        ];

        if ($panel = $this->currentPanel()) {
            $data['panel'] = $panel->toData();
        }

        return $data;
    }

    public function response(int $status = 200, mixed $data = null): JsonResponse
    {
        $payload = [];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        $payload = array_merge($payload, $this->sharedData());

        return response()->json($payload, $status);
    }

    /**
     * Add to the list of paths to discover controllers from.
     */
    public function discover(string $namespace): void
    {
        $this->discoveryPaths[] = $namespace;
    }

    public function getDiscoveryPaths(): array
    {
        return $this->discoveryPaths;
    }

    public function config(): Config
    {
        return app(Config::class);
    }

    public function isPanel(?string $name = null): bool
    {
        $currentPanel = $this->currentPanel();

        if (! $currentPanel) {
            return false;
        }

        if ($name === null) {
            return true;
        }

        return $currentPanel->getName() === $name;
    }

    public function currentPanel(): ?Panel\Panel
    {
        if (! Route::is('hewcode.*')) {
            return null;
        }

        $routeName = Route::currentRouteName();
        $parts = explode('.', $routeName);
        $panelName = $parts[1] ?? null;

        if ($panelName === null) {
            return null;
        }

        return $this->panel($panelName);
    }

    public function routeName(string $name, Panel\Panel|string|null $panel = null): string
    {
        $panel ??= Hewcode::config()->getDefaultPanel();

        if ($panel instanceof Panel\Panel) {
            $panel = $panel->getName();
        }

        return 'hewcode.'.$panel.'.'.$name;
    }

    public function route(string $name, array $parameters = [], bool $absolute = true, Panel\Panel|string|null $panel = null): string
    {
        return route($this->routeName($name, $panel), $parameters, $absolute);
    }

    /**
     * @return array<\Hewcode\Hewcode\Panel\Resource>
     */
    public function getResources(string $panel): array
    {
        $resources = [];

        foreach ($this->discovered($panel) as $class) {
            if (is_a($class, Panel\Resource::class, true)) {
                /** @var \Hewcode\Hewcode\Panel\Resource $resource */
                $resource = app($class);

                if (in_array($panel, $resource->panels())) {
                    $resources[] = $resource;
                }
            }
        }

        return $resources;
    }

    public function getResourceByName(string $name, string $panel): ?Panel\Resource
    {
        foreach ($this->getResources($panel) as $resource) {
            if ($resource->name() === $name) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * Discovers page controllers in configured discovery paths.
     *
     * @return array<int, class-string<PageController>>
     */
    public function discovered(string $panel): array
    {
        $baseDirs = [];
        $discoveryPaths = Hewcode::getDiscoveryPaths() ?? [];

        if (! is_array($discoveryPaths)) {
            $discoveryPaths = [];
        }

        // Resolve relative paths to absolute paths
        foreach ($discoveryPaths as $path) {
            $path = ltrim($path, './');
            $absolutePath = base_path($path);

            if ($absolutePath && is_dir($absolutePath)) {
                $baseDirs[] = $absolutePath;
            }
        }

        if (empty($baseDirs)) {
            return [];
        }

        $files = [];

        foreach ($baseDirs as $base) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }

                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $files[] = $file->getPathname();
            }
        }

        return collect($files)
            ->map(function (string $path) {
                // Get the class name from the file path
                $filename = basename($path, '.php');

                // Extract namespace and class from the file content
                $content = file_get_contents($path);
                $namespace = '';

                if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                    $namespace = trim($matches[1]);
                }

                if ($namespace) {
                    return $namespace . '\\' . $filename;
                }

                return $filename;
            })
            ->filter(function (string $class) use ($panel) {
                if (! class_exists($class) || (! is_a($class, PageController::class, true) && ! is_a($class, Resource::class, true))) {
                    return false;
                }

                $reflection = new ReflectionClass($class);

                if (str_starts_with($reflection->getNamespaceName(), 'Hewcode\Hewcode\Panel\Controllers\Resources')) {
                    return false;
                }

                if ($reflection->isAbstract()) {
                    return false;
                }

                $panels = app($class)->panels();

                if ($panels === true) {
                    return true;
                }

                return in_array($panel, $panels);
            })
            ->values()
            ->all();
    }

    public function panel(?string $name = null): Panel\Panel
    {
        $defaultPanel = Hewcode::config()->getDefaultPanel();
        $name ??= $defaultPanel;

        if (! isset($this->panels[$name])) {
            $this->panels[$name] = new Panel\Panel($name);
        }

        if (count($this->panels) === 1 && $name !== $defaultPanel) {
            Hewcode::config()->setDefaultPanel($name);
        }

        return $this->panels[$name];
    }

    public function panels(): Collection
    {
        return collect($this->panels);
    }

    public function iconRegistry(): Support\IconRegistry
    {
        return app(Support\IconRegistry::class);
    }

    public function registerIcon(?string $iconName): ?array
    {
        return $this->iconRegistry()->register($iconName);
    }

    /**
     * Resolves the first existing page component path from an array of potential paths.
     * Used in Blade templates to ensure Vite only receives valid paths.
     *
     * Supports magic string "hewcode::" which expands to the path configured by
     * HEWCODE_REACT_PATH env var (defaults to "node_modules/@hewcode/react").
     */
    public function resolvePageComponent(array $paths): ?string
    {
        $hewcodeBasePath = env('HEWCODE_REACT_PATH', 'node_modules/@hewcode/react');

        foreach ($paths as $path) {
            $expandedPath = str_replace('hewcode::', $hewcodeBasePath . '/src/', $path);
            $absolutePath = base_path($expandedPath);

            if (file_exists($absolutePath)) {
                return $expandedPath;
            }
        }

        return null;
    }
}
