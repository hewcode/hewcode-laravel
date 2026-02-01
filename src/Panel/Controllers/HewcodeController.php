<?php

namespace Hewcode\Hewcode\Panel\Controllers;

use Hewcode\Hewcode\Actions\Action;
use Hewcode\Hewcode\Contracts\HasRecord;
use Hewcode\Hewcode\Contracts\HasVisibility;
use Hewcode\Hewcode\Contracts\MountsActions;
use Hewcode\Hewcode\Contracts\MountsComponents;
use Hewcode\Hewcode\Contracts\ResolvesRecords;
use Hewcode\Hewcode\Forms\Form;
use Hewcode\Hewcode\Support\Container;
use Hewcode\Hewcode\Widgets\Widget;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Hewcode\Hewcode\exposed;
use function Hewcode\Hewcode\generateComponentHash;

class HewcodeController extends Controller
{
    public function __invoke(Request $request): mixed
    {
        $request->validate([
            'call.name' => 'required|string',
            'call.params' => 'sometimes|array',
            'context.recordId' => 'sometimes|string',
            'context.recordIds' => 'sometimes|array',
            'context.model' => 'sometimes|string',
            'context.state' => 'sometimes|array',
            'seal.component' => 'required|string',
            'seal.route' => 'required|string',
            'seal.hash' => 'required|string',
            'seal.timestamp' => 'required|integer',
            'seal.nonce' => 'required|string',
        ]);

        $componentName = $request->input('seal.component');
        $routeName = $request->input('seal.route');
        $callName = $request->input('call.name');
        $recordId = $request->input('context.recordId');
        $recordIds = $request->input('context.recordIds');
        $model = $request->input('context.model');
        $state = $request->input('context.state', []);

        // Check seal expiration (1 hour)
        $maxAge = 3600;
        $timestamp = $request->input('seal.timestamp');
        if (time() - $timestamp > $maxAge) {
            abort(419, app()->environment('local') ? 'Seal expired' : '');
        }

        // Verify seal hash
        $seal = generateComponentHash(
            $componentName,
            $routeName,
            $timestamp,
            $request->input('seal.nonce')
        );

        if (! hash_equals($seal['hash'], $request->input('seal.hash'))) {
            abort(419, app()->environment('local') ? 'Invalid seal. Given ['.$request->input('seal.hash').'], expected ['.$seal['hash'].']' : '');
        }

        $route = Route::getRoutes()->getByName($routeName);

        if ($route) {
            $this->applyRouteMiddleware($request, $route);
        }

        $controller = $route?->getController();

        if (! ($controller instanceof ServeResourceController) && ! method_exists($controller, $componentName)) {
            abort(404, app()->environment('local') ? "Component [$componentName] not found on controller ".get_class($controller) : '');
        }

        if ($controller instanceof ServeResourceController) {
            /** @var Container $component */
            $component = $controller->getComponent($componentName, $routeName);
        } else {
            /** @var Container $component */
            $component = $controller->{$componentName}();
        }

        if (! $component instanceof Container) {
            abort(500, app()->environment('local') ? "Component [$componentName] on controller ".get_class($controller).' must return an instance of '.Container::class : '');
        }

        $component
            ->name($componentName)
            ->route($routeName);

        if ($component instanceof Form && ! empty($state)) {
            $component->state($state);
        }

        $component->prepare();

        if (! $component instanceof HasVisibility || ! $component->isVisible()) {
            abort(403, app()->environment('local') ? 'Access denied' : '');
        }

        if ($component instanceof ResolvesRecords) {
            if ($recordId) {
                $record = $component->resolveRecord($recordId);

                if ($component instanceof HasRecord) {
                    $component->record($record);
                }
            } elseif ($recordIds) {
                $actionArgs['records'] = $component->resolveRecords($recordIds);
            }
        }

        if (! $recordId && ! $recordIds && $model) {
            $modelClass = Relation::getMorphedModel($model) ?? $model;

            $component->model($modelClass);
        }

        if ($callName === 'mount') {
            $data = $request->validate([
                'call.params.name' => 'required|string',
                'call.params.args' => 'sometimes|array',
            ]);

            $mountName = $data['call']['params']['name'];
            $mountArgs = $data['call']['params']['args'] ?? [];

            // remove componentName. prefix from actionName if present
            if (str_starts_with($mountName, $componentName.'.')) {
                $mountName = substr($mountName, strlen($componentName) + 1);
            }

            // now we will need to create a method that can traverse the mountName path recursively
            // and call a getComponent method on every part of the path, until we reach the final part
            // let's assume we already have that implemented as $this->getMountableComponent($component, $mountName)
            $mountableComponent = $this->getMountableComponent($component, $mountName);

            if (! $mountableComponent) {
                throw new HttpException(400);
            }

            if ($component instanceof MountsActions && $mountableComponent instanceof Action) {
                return $this->mountAction($component, $mountableComponent, $mountArgs);
            }

            if (is_array($mountableComponent) && is_callable($mountableComponent)) {
                return $this->primitiveToJsonResponse(
                    call_user_func_array($mountableComponent, $mountArgs)
                );
            }

            if ($mountableComponent instanceof Widget) {
                return response()->json($mountableComponent->toData());
            }
        }

        throw new HttpException(400);
    }

    protected function mountAction(MountsActions $component, Action $action, array $args): mixed
    {
        return $component->mountAction($action, $args);
    }

    protected function primitiveToJsonResponse(mixed $response): mixed
    {
        if (is_array($response) || $response instanceof Arrayable) {
            return response()->json($response);
        }

        if (is_bool($response) || is_int($response) || is_float($response) || is_string($response) || $response === null) {
            return response()->json($response);
        }

        return $response;
    }

    protected function applyRouteMiddleware(Request $request, RoutingRoute $route): void
    {
        $appliedMiddleware = app()->shouldSkipMiddleware() ? [] : Route::gatherRouteMiddleware($request->route());

        $routeMiddleware = app()->shouldSkipMiddleware() ? [] : Route::gatherRouteMiddleware($route);

        $middleware = array_diff($routeMiddleware, $appliedMiddleware);

        if (empty($middleware)) {
            return;
        }

        app(Pipeline::class)
            ->send($request)
            ->through($middleware)
            ->then(fn ($request) => new \Illuminate\Http\Response);
    }

    protected function getMountableComponent(Container $component, string $mountName): mixed
    {
        $pathParts = explode('.', $mountName);
        $current = $component;

        foreach ($pathParts as $i => $part) {
            $isLastPart = ($i === count($pathParts) - 1);

            // Try to get it as a child component
            if ($current instanceof MountsComponents) {
                $child = $current->getComponent($part);

                if ($child !== null) {
                    $current = $child;

                    continue;
                }
            }

            // If not a child component, check if it's an exposed method on current
            if ($isLastPart && method_exists($current, $part) && exposed($current, $part)) {
                return [$current, $part];
            }

            // Could not resolve this part of the path
            return null;
        }

        return $current;
    }
}
