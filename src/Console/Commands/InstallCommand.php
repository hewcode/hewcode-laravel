<?php

namespace Hewcode\Hewcode\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'hew:install')]
class InstallCommand extends Command
{
    protected $signature = 'hew:install
                            {--with-inertia : Install Inertia.js first}
                            {--force : Overwrite existing files without confirmation}
                            {--dry-run : Show what would be changed without making changes}
                            {--skip-npm : Skip npm package installation}
                            {--panel : Register Hewcode panel in AppServiceProvider}';

    protected $description = 'Install Hewcode Laravel package';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->components->info('Running in dry-run mode - no files will be modified');
            $this->newLine();
        }

        // Check if Inertia should be installed first
        if ($this->option('with-inertia')) {
            $this->components->info('Installing Inertia.js...');
            $this->installInertia();
            $this->newLine();
        } elseif (! $this->option('dry-run') && ! $this->isInertiaInstalled()) {
            $this->components->error('Inertia.js is not installed!');
            $this->components->warn('Hewcode requires Inertia.js to be installed first. You have two options:');
            $this->components->bulletList([
                'Run: php artisan hew:install --with-inertia (recommended)',
                'Or manually install Inertia.js first: https://inertiajs.com/docs/v2/installation',
            ]);

            return self::FAILURE;
        }

        $this->components->info('Installing Hewcode...');

        $this->updateAppFile();
        $this->updateViteConfig();
        $this->updateAppBlade();
        $this->updateAppCss();
        $this->registerPanel();

        if (! $this->option('dry-run')) {
            if (! $this->option('skip-npm')) {
                $this->installNpmPackages();
            }
            $this->displayNextSteps();
            $this->components->info('Hewcode installation complete!');
        } else {
            $this->newLine();
            $this->components->info('Dry run complete - no files were modified');
        }

        return self::SUCCESS;
    }

    protected function updateAppFile(): void
    {
        // Try to find the app file with various extensions
        $possiblePaths = [
            resource_path('js/app.tsx'),
            resource_path('js/app.ts'),
            resource_path('js/app.jsx'),
            resource_path('js/app.js'),
        ];

        $appFilePath = null;
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                $appFilePath = $path;
                break;
            }
        }

        if (! $appFilePath) {
            $this->components->warn('File not found: resources/js/app.{tsx,ts,jsx,js}');
            $this->components->warn('Make sure you have Inertia.js set up first.');

            return;
        }

        $fileName = basename($appFilePath);
        $content = File::get($appFilePath);

        // Check if already updated
        if (str_contains($content, 'HewcodeProvider')) {
            $this->components->info("✓ {$fileName} already configured");

            return;
        }

        // Ensure CSS is imported
        $cssImportPattern = "/import\s+['\"]\.\.\/css\/app\.css['\"]/";
        if (! preg_match($cssImportPattern, $content)) {
            // Add CSS import at the top of the file, before other imports
            $content = "import '../css/app.css';\n".$content;
        }

        // Add HewcodeProvider import
        if (! str_contains($content, "import HewcodeProvider from '@hewcode/react/layouts/provider'")) {
            // Try to add after react or react-dom import
            $content = preg_replace(
                "/(import.*?from\s+['\"]react(-dom\/client)?['\"];?\n)/",
                "$1import HewcodeProvider from '@hewcode/react/layouts/provider';\n",
                $content,
                1
            );
        }

        // Determine file extension for glob patterns
        $extension = pathinfo($appFilePath, PATHINFO_EXTENSION);

        // Update resolve function
        // Note: @hewcode/react pages are always .tsx regardless of user's app extension
        $content = preg_replace(
            '/resolve:\s*\(name\)\s*=>\s*resolvePageComponent\(\s*`\.\/pages\/\$\{name\}\.'.$extension.'`,\s*import\.meta\.glob\([\'"]\.\/pages\/\*\*\/\*\.'.$extension.'[\'"]\)\s*\)/s',
            "resolve: (name) =>\n        resolvePageComponent([`./pages/\${name}.{$extension}`, `../../node_modules/@hewcode/react/src/pages/\${name}.tsx`], {\n            ...import.meta.glob('./pages/**/*.{$extension}'),\n            ...import.meta.glob('../../node_modules/@hewcode/react/src/pages/hewcode/**/*.tsx'),\n        })",
            $content
        );

        // Wrap App with HewcodeProvider
        $content = preg_replace(
            '/root\.render\(\s*<App\s*\{\.\.\.props\}\s*\/>\s*\)/s',
            "root.render(\n            <HewcodeProvider {...props}>\n                <App {...props} />\n            </HewcodeProvider>\n        )",
            $content
        );

        if ($this->option('dry-run')) {
            $this->components->info("Would update: {$fileName}");
        } else {
            File::put($appFilePath, $content);
            $this->components->info("✓ Updated {$fileName}");
        }
    }

    protected function updateViteConfig(): void
    {
        // Try to find the vite config file
        $possiblePaths = [
            base_path('vite.config.ts'),
            base_path('vite.config.js'),
        ];

        $viteConfigPath = null;
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                $viteConfigPath = $path;
                break;
            }
        }

        if (! $viteConfigPath) {
            $this->components->warn('File not found: vite.config.{ts,js}');

            return;
        }

        $fileName = basename($viteConfigPath);
        $content = File::get($viteConfigPath);

        // Check if already updated
        if (str_contains($content, '@vitejs/plugin-react')) {
            $this->components->info("✓ {$fileName} already configured");

            return;
        }

        // Add react import
        if (! str_contains($content, "import react from '@vitejs/plugin-react'")) {
            $content = preg_replace(
                "/(import laravel from 'laravel-vite-plugin';?\n)/",
                "$1import react from '@vitejs/plugin-react';\n",
                $content
            );
        }

        // Add react() to plugins array
        $content = preg_replace(
            '/(plugins:\s*\[\s*laravel\([^)]+\),?)/s',
            "$1\n        react(),",
            $content
        );

        // Update input paths if app.js exists in config but actual file is different
        $actualAppExtension = $this->findAppFileExtension();
        if ($actualAppExtension && $actualAppExtension !== 'js') {
            $content = preg_replace(
                "/'resources\/js\/app\.js'/",
                "'resources/js/app.{$actualAppExtension}'",
                $content
            );
        }

        if ($this->option('dry-run')) {
            $this->components->info("Would update: {$fileName}");
        } else {
            File::put($viteConfigPath, $content);
            $this->components->info("✓ Updated {$fileName}");
        }
    }

    protected function updateAppBlade(): void
    {
        $appBladePath = resource_path('views/app.blade.php');

        if (! File::exists($appBladePath)) {
            $this->components->warn("File not found: {$appBladePath}");

            return;
        }

        $content = File::get($appBladePath);

        // Check if already updated
        if (str_contains($content, 'Hewcode::resolvePageComponent')) {
            $this->components->info('✓ app.blade.php already configured');

            return;
        }

        // Find the app file extension
        $appFileExtension = $this->findAppFileExtension();
        if (! $appFileExtension) {
            $this->components->warn('Could not find app file to determine extension');

            return;
        }

        // Replace @vite directive
        // Note: Hewcode pages are always .tsx
        $viteDirective = <<<BLADE
@vite([
    'resources/js/app.{$appFileExtension}',
    \Hewcode\Hewcode\Hewcode::resolvePageComponent([
        "resources/js/pages/{\$page['component']}.{$appFileExtension}",
        "hewcode::pages/{\$page['component']}.tsx"
    ])
])
BLADE;

        $content = preg_replace(
            "/@vite\(\[?\s*['\"]resources\/js\/app\.(tsx|ts|jsx|js)['\"],?\s*\]?\)/s",
            $viteDirective,
            $content
        );

        if ($this->option('dry-run')) {
            $this->components->info('Would update: app.blade.php');
        } else {
            File::put($appBladePath, $content);
            $this->components->info('✓ Updated app.blade.php');
        }
    }

    protected function updateAppCss(): void
    {
        $appCssPath = resource_path('css/app.css');

        if (! File::exists($appCssPath)) {
            $this->components->warn("File not found: {$appCssPath}");

            return;
        }

        $content = File::get($appCssPath);

        // Check if already updated
        if (str_contains($content, "@import '@hewcode/react/styles/globals.css'")) {
            $this->components->info('✓ app.css already configured');

            return;
        }

        // Add import at the top
        $import = "@import '@hewcode/react/styles/globals.css';\n\n";
        $content = $import.$content;

        if ($this->option('dry-run')) {
            $this->components->info('Would update: app.css');
        } else {
            File::put($appCssPath, $content);
            $this->components->info('✓ Updated app.css');
        }
    }

    protected function registerPanel(): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');

        if (! File::exists($providerPath)) {
            $this->components->warn('AppServiceProvider.php not found - skipping panel registration');

            return;
        }

        $content = File::get($providerPath);

        // Check if already registered
        if (str_contains($content, 'Hewcode::panel()')) {
            $this->components->info('✓ Hewcode panel already registered');

            return;
        }

        if ($this->option('dry-run')) {
            $this->components->info('Would register Hewcode panel in AppServiceProvider');

            return;
        }

        // Prompt for confirmation unless --panel flag is passed
        if (! $this->option('panel') && ! $this->components->confirm('Register Hewcode panel in AppServiceProvider?', false)) {
            $this->components->warn('Skipped panel registration');

            return;
        }

        // Add use statement if not present
        if (! str_contains($content, 'use Hewcode\Hewcode\Hewcode;')) {
            $content = preg_replace(
                '/(namespace App\\\\Providers;)/',
                "$1\n\nuse Hewcode\\Hewcode\\Hewcode;",
                $content,
                1
            );
        }

        // Find the register method and add Hewcode::panel()
        if (preg_match('/public function register\(\):\s*void\s*\{/', $content)) {
            // Register method exists, add the call at the beginning
            $content = preg_replace(
                '/(public function register\(\):\s*void\s*\{)/',
                "$1\n        Hewcode::panel();",
                $content,
                1
            );

            File::put($providerPath, $content);
            $this->components->info('✓ Registered Hewcode panel in AppServiceProvider');
        } else {
            $this->components->warn('Could not find register() method in AppServiceProvider');
            $this->components->warn('Please manually add Hewcode::panel() to your AppServiceProvider::register() method');
        }
    }

    protected function findAppFileExtension(): ?string
    {
        $possiblePaths = [
            resource_path('js/app.tsx') => 'tsx',
            resource_path('js/app.ts') => 'ts',
            resource_path('js/app.jsx') => 'jsx',
            resource_path('js/app.js') => 'js',
        ];

        foreach ($possiblePaths as $path => $extension) {
            if (File::exists($path)) {
                return $extension;
            }
        }

        return null;
    }

    protected function installNpmPackages(): void
    {
        $this->newLine();
        $this->components->info('Installing npm packages...');

        // Check if npm is available
        exec('command -v npm', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->components->warn('npm is not available. Please install npm packages manually:');
            $this->components->warn('npm install @hewcode/react @vitejs/plugin-react');

            return;
        }

        // Check if package.json exists
        if (! File::exists(base_path('package.json'))) {
            $this->components->warn('package.json not found. Skipping npm installation.');

            return;
        }

        $process = proc_open(
            'npm install @hewcode/react @vitejs/plugin-react',
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path()
        );

        if (is_resource($process)) {
            fclose($pipes[0]);

            while ($line = fgets($pipes[1])) {
                $this->line('  '.$line);
            }

            fclose($pipes[1]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            if ($returnCode === 0) {
                $this->components->info('✓ npm packages installed successfully');
            } else {
                $this->components->error('Failed to install npm packages');
                $this->components->warn('Please run manually: npm install @hewcode/react @vitejs/plugin-react');
            }
        }
    }

    protected function isInertiaInstalled(): bool
    {
        // Check if Inertia is in the app's composer.json (not just as a transitive dependency)
        $composerJson = base_path('composer.json');
        if (! File::exists($composerJson)) {
            return false;
        }

        $composerData = json_decode(File::get($composerJson), true);

        return isset($composerData['require']['inertiajs/inertia-laravel']);
    }

    protected function installInertia(): void
    {
        if ($this->option('dry-run')) {
            $this->components->info('Would install Inertia.js (server and client)');

            return;
        }

        // Install server-side package
        $this->components->info('Installing Inertia server-side package...');
        $this->executeCommand('composer require inertiajs/inertia-laravel');

        // Publish middleware
        $this->components->info('Publishing Inertia middleware...');
        $this->executeCommand('php artisan inertia:middleware');

        // Install client-side package
        if (! $this->option('skip-npm')) {
            $this->components->info('Installing Inertia client-side package...');
            $this->executeNpmCommand('npm install @inertiajs/react');
        }

        // Create app.blade.php if it doesn't exist
        $this->createAppBlade();

        // Create app.tsx if it doesn't exist
        $this->createAppFile();

        // Register middleware
        $this->registerInertiaMiddleware();

        $this->components->info('✓ Inertia.js installed successfully');
    }

    protected function createAppBlade(): void
    {
        $appBladePath = resource_path('views/app.blade.php');

        if (File::exists($appBladePath)) {
            $this->components->info('✓ app.blade.php already exists');

            return;
        }

        $appFileExtension = $this->findAppFileExtension() ?? 'tsx';

        $template = <<<BLADE
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @viteReactRefresh
        @vite(['resources/js/app.{$appFileExtension}'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
BLADE;

        File::put($appBladePath, $template);
        $this->components->info('✓ Created app.blade.php');
    }

    protected function createAppFile(): void
    {
        // Check if any app file already exists with Inertia setup
        $possiblePaths = [
            resource_path('js/app.tsx'),
            resource_path('js/app.ts'),
            resource_path('js/app.jsx'),
            resource_path('js/app.js'),
        ];

        $fileToDelete = null;
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                $content = File::get($path);
                // Check if it already has Inertia setup
                if (str_contains($content, 'createInertiaApp') || str_contains($content, '@inertiajs')) {
                    $this->components->info('✓ '.basename($path).' already exists with Inertia setup');

                    return;
                }
                // File exists but doesn't have Inertia - mark for deletion and recreate
                $this->components->warn(basename($path).' exists but missing Inertia setup - recreating...');
                $fileToDelete = $path;
                break;
            }
        }

        // Create resources/js directory if it doesn't exist
        $jsDir = resource_path('js');
        if (! File::exists($jsDir)) {
            File::makeDirectory($jsDir, 0755, true);
        }

        // Determine which file extension to use (tsx for new projects, jsx otherwise)
        // Use JSX extension since we're using JSX syntax
        $useTypeScript = ! File::exists(resource_path('js/app.js')) && ! File::exists(resource_path('js/app.jsx'));
        $appFilePath = $useTypeScript ? resource_path('js/app.tsx') : resource_path('js/app.jsx');
        $extension = $useTypeScript ? 'tsx' : 'jsx';

        // Delete the old file if it's being replaced
        if ($fileToDelete && $fileToDelete !== $appFilePath && File::exists($fileToDelete)) {
            File::delete($fileToDelete);
            $this->components->info('✓ Deleted old '.basename($fileToDelete));
        }

        $template = <<<JAVASCRIPT
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `\${title} - \${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/\${name}.{$extension}`,
            import.meta.glob('./pages/**/*.{$extension}')
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
JAVASCRIPT;

        File::put($appFilePath, $template);
        $this->components->info('✓ Created '.basename($appFilePath));

        // Create resources/css directory and app.css if needed
        $cssDir = resource_path('css');
        if (! File::exists($cssDir)) {
            File::makeDirectory($cssDir, 0755, true);
        }

        $appCssPath = resource_path('css/app.css');
        if (! File::exists($appCssPath)) {
            File::put($appCssPath, '');
            $this->components->info('✓ Created app.css');
        }

        // Create pages directory
        $pagesDir = resource_path('js/pages');
        if (! File::exists($pagesDir)) {
            File::makeDirectory($pagesDir, 0755, true);
            $this->components->info('✓ Created pages directory');
        }
    }

    protected function registerInertiaMiddleware(): void
    {
        $bootstrapAppPath = base_path('bootstrap/app.php');

        if (! File::exists($bootstrapAppPath)) {
            $this->components->warn('bootstrap/app.php not found - skipping middleware registration');
            $this->components->warn('Please manually add HandleInertiaRequests to your web middleware');

            return;
        }

        $content = File::get($bootstrapAppPath);

        // Check if middleware is already registered
        if (str_contains($content, 'HandleInertiaRequests')) {
            $this->components->info('✓ Inertia middleware already registered');

            return;
        }

        // Look for an existing withMiddleware block and add to it
        if (preg_match('/->withMiddleware\(function \(Middleware \$middleware\).*?\{(.*?)\}\)/s', $content, $matches)) {
            // There's already a withMiddleware block, add our middleware to it
            $middlewareCode = "\n        \$middleware->web(append: [\n            \\App\\Http\\Middleware\\HandleInertiaRequests::class,\n        ]);";

            $content = preg_replace(
                '/(->withMiddleware\(function \(Middleware \$middleware\).*?\{)/s',
                "$1{$middlewareCode}",
                $content,
                1,
                $count
            );
        } else {
            // No withMiddleware block exists, add one after withRouting
            $middlewareBlock = "\n    ->withMiddleware(function (Middleware \$middleware) {\n        \$middleware->web(append: [\n            \\App\\Http\\Middleware\\HandleInertiaRequests::class,\n        ]);\n    })";

            $content = preg_replace(
                '/(->withRouting\([^)]*\))/s',
                "$1{$middlewareBlock}",
                $content,
                1,
                $count
            );
        }

        if (isset($count) && $count > 0) {
            File::put($bootstrapAppPath, $content);
            $this->components->info('✓ Registered Inertia middleware');
        } else {
            $this->components->warn('Could not automatically register Inertia middleware');
            $this->components->warn('Please manually add HandleInertiaRequests to your web middleware in bootstrap/app.php');
        }
    }

    protected function executeCommand(string $command): void
    {
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path()
        );

        if (is_resource($process)) {
            fclose($pipes[0]);

            while ($line = fgets($pipes[1])) {
                $this->line('  '.trim($line));
            }

            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }
    }

    protected function executeNpmCommand(string $command): void
    {
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path()
        );

        if (is_resource($process)) {
            fclose($pipes[0]);

            while ($line = fgets($pipes[1])) {
                $this->line('  '.trim($line));
            }

            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }
    }

    protected function displayNextSteps(): void
    {
        $this->newLine();
        $this->components->info('Next steps:');
        $this->newLine();

        $steps = [];

        if ($this->option('skip-npm')) {
            $steps[] = 'Run: npm install @hewcode/react @vitejs/plugin-react';
        }

        $steps[] = 'Run: npm run build';
        $steps[] = 'Check the documentation at packages/docs/ for more details';

        $this->components->bulletList($steps);
    }
}
