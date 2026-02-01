<?php

namespace Hewcode\Hewcode\Concerns;

use Hewcode\Hewcode\Actions\Action;
use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Support\Component;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

trait InteractsWithActions
{
    public function mountAction(Action $action, array $args): mixed
    {
        if (! $action->isVisible()) {
            abort(403, 'Unauthorized.');
        }

        $response = $action->execute($args);

        if ($response instanceof Responsable) {
            return $response->toResponse(request());
        }

        if ($response instanceof Response) {
            return $response;
        }

        return Hewcode::response(data: $response ?? []);
    }

    private function filterMountableActions(iterable $actions, Component $parent): Collection
    {
        return collect($actions)
            ->filter(function (Action $action) use ($parent) {
                if ($this instanceof \Hewcode\Hewcode\Contracts\HasRecord) {
                    $action->record($this->getRecord());
                }

                return $action
                    ->parent($action->parent ?? $parent)
                    ->model($this->getModel())
                    ->isVisible();
            });
    }
}
