<?php

namespace Hewcode\Hewcode\Support;

use Illuminate\Support\Facades\Route;

use function Hewcode\Hewcode\generateComponentHash;

class Container extends Component
{
    /**
     * @internal
     */
    public ?string $route = null;

    /**
     * @internal
     */
    public function route(?string $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function getSealProps(): array
    {
        if ($parent = $this->findParentContainer($this)) {
            return $parent->getSealProps();
        }

        $route = $this->route ?? Route::currentRouteName();

        return array_merge(generateComponentHash($name = $this->getName(), $route), [
            'component' => $name,
            'route' => $route,
        ]);
    }

    public function prepare(): void
    {
        //
    }

    public function toData(): array
    {
        $this->prepare();

        return array_merge(parent::toData(), [
            'seal' => $this->getSealProps(),
        ]);
    }

    private function findParentContainer(Component $container): ?self
    {
        if (! ($parent = $container->getParent())) {
            return null;
        }

        if ($parent instanceof self) {
            return $parent;
        }

        if ($parent->getParent()) {
            return $this->findParentContainer($parent);
        }

        return null;
    }
}
