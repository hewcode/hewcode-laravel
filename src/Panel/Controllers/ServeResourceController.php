<?php

namespace Hewcode\Hewcode\Panel\Controllers;

use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Panel\Controllers\Resources\ResourceController;
use Hewcode\Hewcode\Support\Container;
use Illuminate\Support\Facades\Route;

class ServeResourceController
{
    public function __invoke()
    {
        return app()->call($this->getPageController());
    }

    protected function getPageController(?string $routeName = null): ResourceController
    {
        if (! $routeName) {
            $path = request()->path();
            $panel = explode('/', $path)[0];
            $name = explode('/', $path)[1];

            $routeName = Route::currentRouteName();
        } else {
            $panel = explode('.', $routeName)[1];
            $name = explode('.', $routeName)[2];
        }

        if (empty($name) || empty($panel)) {
            abort(404);
        }

        $resource = Hewcode::getResourceByName($name, $panel);

        if (! $resource) {
            abort(404);
        }

        /** @var ResourceController $page */
        foreach ($resource->getPageControllers() as $page) {
            if ($routeName === Hewcode::routeName($page->getRouteName(), $panel)) {
                return $page;
            }
        }

        abort(404);
    }

    public function getComponent(string $componentName, string $routeName): Container
    {
        $controller = $this->getPageController($routeName);

        if (! method_exists($controller, $componentName)) {
            abort(404, app()->environment('local') ? "Component [$componentName] not found on controller ".get_class($controller) : '');
        }

        return $controller->{$componentName}();
    }
}
