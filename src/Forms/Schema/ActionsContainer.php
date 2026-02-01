<?php

namespace Hewcode\Hewcode\Forms\Schema;

use Hewcode\Hewcode\Actions\Action;
use Hewcode\Hewcode\Concerns;
use Hewcode\Hewcode\Contracts;
use Hewcode\Hewcode\Forms\Form;
use Hewcode\Hewcode\Support\Component;
use Hewcode\Hewcode\Support\ComponentCollection;
use Illuminate\Database\Eloquent\Model;

class ActionsContainer extends Field implements Contracts\MountsActions, Contracts\MountsComponents
{
    use Concerns\InteractsWithActions;

    /** @var array<Action> */
    protected array $actions = [];

    public function actions(array $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /** @return array<Action> */
    public function getMountableActions(): array
    {
        /** @var Form $form */
        $form = $this->getParent();

        return array_filter(
            array_map(
                fn (Action $action) => $action
                    ->shareEvaluationParameters($this->getAllEvaluationParameters())
                    ->parent($this)
                    ->record($this->record)
                    ->model($this->model instanceof Model ? $this->model : null)
                    ->withPublicContext('state', $form->getState()),
                $this->actions
            ),
            fn (Action $action) => $action->isVisible()
        );
    }

    public function getComponent(string $name): Component|ComponentCollection|null
    {
        return match ($name) {
            'actions' => new ComponentCollection($this->filterMountableActions($this->getMountableActions(), $this)->all()),
            default => null,
        };
    }

    protected function getFieldType(): string
    {
        return 'actions';
    }

    protected function getFieldSpecificData(): array
    {
        return [
            'actions' => array_map(fn (Action $action) => $action->toData(), $this->getMountableActions()),
        ];
    }

    protected function getEvaluationParameters(): array
    {
        return [
            'field' => $this,
            'record' => $this->record,
            'model' => $this->model,
            'parent' => $this->parent,
        ];
    }
}
