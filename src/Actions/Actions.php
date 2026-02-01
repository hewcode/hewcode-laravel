<?php

namespace Hewcode\Hewcode\Actions;

use Hewcode\Hewcode\Concerns;
use Hewcode\Hewcode\Contracts;
use Hewcode\Hewcode\Support\Component;
use Hewcode\Hewcode\Support\ComponentCollection;
use Hewcode\Hewcode\Support\Container;
use Hewcode\Hewcode\Support\Context;

class Actions extends Container implements Contracts\HasRecord, Contracts\HasVisibility, Contracts\MountsActions, Contracts\MountsComponents, Contracts\ResolvesRecords
{
    use Concerns\HasContext;
    use Concerns\HasFormDefinition;
    use Concerns\HasRecord;
    use Concerns\InteractsWithActions;
    use Concerns\RequiresVisibility;
    use Concerns\ResolvesRecords;

    public function __construct(
        /** @var Action[] $actions */
        protected array $actions = []
    ) {
        $this->setUp();
    }

    protected function setUp(): void
    {
        $this->context(new Context);
    }

    public static function make(array $actions): static
    {
        return new static($actions);
    }

    protected function getEvaluationParameters(): array
    {
        $parameters = [];

        if ($model = $this->getModel()) {
            $parameters['model'] = $model;
        }

        return $parameters;
    }

    public function toData(): array
    {
        return array_merge(parent::toData(), [
            'actions' => collect($this->getMountableActions())
                ->mapWithKeys(fn (Action $action) => [
                    $action->name => $action->toData(),
                ])
                ->toArray(),
        ]);
    }

    public function getMountableActions(): array
    {
        return collect($this->actions)
            ->filter(fn (Action $action) => $action
                ->model($this->getModel())
                ->context($this->context)
                ->record($this->getRecord())
                ->parent($this)
                ->isVisible()
            )
            ->toArray();
    }

    public function getComponent(string $name): Component|ComponentCollection|null
    {
        return (new ComponentCollection($this->getMountableActions()))->getComponent($name);
    }
}
