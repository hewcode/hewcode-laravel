<?php

namespace Hewcode\Hewcode\Http;

use Hewcode\Hewcode\Hewcode;
use Illuminate\Support\Facades\Route;
use Inertia\ProvidesInertiaProperties;
use Inertia\RenderContext;
use function Hewcode\Hewcode\flattenLocaleArray;

class InertiaProps implements ProvidesInertiaProperties
{
    public function toInertiaProperties(RenderContext $context): iterable
    {
        $props = [
            'hewcode' => array_merge([
                'name' => config('app.name'),
                'locale' => [
                    'lang' => $context->request->getLocale(),
                    'messages' => flattenLocaleArray(is_array(__('hewcode::hewcode')) ? __('hewcode::hewcode') : [], 'hewcode'),
                ],
                'csrfToken' => csrf_token(),
                'developerMode' => config('app.debug', false),
                'auth' => [
                    'user' => $context->request->user(),
                ],
                'sidebarOpen' => ! $context->request->hasCookie('sidebar_state') || $context->request->cookie('sidebar_state') === 'true',
                'routes' => $this->serializeRoutes(),
            ], Hewcode::sharedData()),
        ];

        $props['hewcode']['icons'] = Hewcode::iconRegistry()->all();

        return $props;
    }

    protected function serializeRoutes(): array
    {
        return collect($this->getSerializedRoutes())
            ->mapWithKeys(function (string $routeName) {
                if (str_starts_with($routeName, 'panel::')) {
                    if (! $panel = Hewcode::currentPanel()) {
                        return [];
                    }

                    $route = Hewcode::routeName(str($routeName)->after('panel::')->toString(), $panel);
                } else {
                    $route = $routeName;
                }

                $uri = Route::getRoutes()->getByName($route)?->uri() ?? '';

                return [$routeName => url($uri)];
            })
            ->toArray();
    }

    protected function getSerializedRoutes(): array
    {
        return [
            'hewcode.mount',

            'panel::dashboard',

            'panel::settings',
            'panel::profile.edit',
            'panel::profile.update',
            'panel::profile.destroy',
            'panel::appearance.edit',
            'panel::password.edit',

            'panel::password.request',
            'panel::password.confirm.store',
            'panel::password.email',
            'panel::password.store',
            'panel::password.update',
            'panel::verification.send',
            'panel::login',
            'panel::login.store',
            'panel::logout',
            'panel::register',
            'panel::register.store',
        ];
    }
}
