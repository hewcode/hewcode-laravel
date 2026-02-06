<?php

namespace Hewcode\Hewcode;

use Hewcode\Hewcode\Http\InertiaProps;
use Hewcode\Hewcode\Panel\Controllers\HewcodeController;
use Hewcode\Hewcode\Support\Config;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class HewcodeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('hewcode')
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function packageRegistered()
    {
        $this->app->singleton(Config::class, function () {
            return new Config;
        });

        $this->app->singleton(Manager::class, function () {
            return new Manager;
        });

        $this->app->singleton(Support\IconRegistry::class, function () {
            return new Support\IconRegistry;
        });

        $this->app->alias(Manager::class, 'hewcode');

        $this->app->afterResolving(Middleware::class, function (Middleware $middleware) {
            $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
            $middleware->web(append: [
                Http\Middleware\HandleAppearance::class,
            ]);
        });
    }

    public function packageBooted()
    {
        Route::post('/_hewcode', HewcodeController::class)
            ->middleware('web')
            ->name('hewcode.mount');

        Hewcode::panels()->each(function (Panel\Panel $panel) {
            $panel->register();
        });

        Inertia::share(new InertiaProps);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\InstallCommand::class,
                Console\Commands\MakeResourceCommand::class,
                Console\Commands\MakeListingCommand::class,
                Console\Commands\MakeFormCommand::class,
                Console\Commands\MakePageCommand::class,
                Console\Commands\MakeResourcePageCommand::class,
                Console\Commands\FindHardcodedStringsCommand::class,
            ]);
        }
    }
}
