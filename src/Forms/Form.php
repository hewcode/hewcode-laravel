<?php

namespace Hewcode\Hewcode\Forms;

use Closure;
use Hewcode\Hewcode\Actions\Action;
use Hewcode\Hewcode\Concerns;
use Hewcode\Hewcode\Contracts;
use Hewcode\Hewcode\Forms\Schema\Field;
use Hewcode\Hewcode\Forms\Schema\Wizard\Step;
use Hewcode\Hewcode\Support\Component;
use Hewcode\Hewcode\Support\ComponentCollection;
use Hewcode\Hewcode\Support\Container;
use Hewcode\Hewcode\Support\Context;
use Hewcode\Hewcode\Support\Expose;
use Hewcode\Hewcode\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class Form extends Container implements Contracts\HasRecord, Contracts\HasVisibility, Contracts\MountsActions, Contracts\MountsComponents, Contracts\ResolvesRecords
{
    use Concerns\HasContext;
    use Concerns\HasRecord;
    use Concerns\InteractsWithActions;
    use Concerns\RequiresVisibility;
    use Concerns\ResolvesRecords;

    /** @var array<Field> */
    protected array $fields = [];

    protected array $state = [];

    protected ?Closure $submitUsing = null;

    protected ?Closure $submitAction = null;

    protected ?Closure $fillUsing = null;

    /** @var array<Action> */
    protected array $footerActions = [];

    // Wizard properties
    /** @var array<Step> */
    protected array $wizardSteps = [];

    protected bool $wizardSkippable = true;

    protected bool $wizardShowFooterActionsInLastStep = true;

    public function __construct()
    {
        $this->setUp();
    }

    protected function setUp(): void
    {
        $this->context(new Context);
        $this->fillUsing($this->fillForm(...));
    }

    public static function make(?string $name = 'form'): static
    {
        return (new static)->name($name);
    }

    public function schema(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @param  array<Step>  $steps
     */
    public function wizard(array $steps): static
    {
        $this->wizardSteps = $steps;

        return $this;
    }

    public function skippable(bool $skippable = true): static
    {
        $this->wizardSkippable = $skippable;

        return $this;
    }

    public function showFooterActionsInLastStep(bool $showFooterActionsInLastStep = true): static
    {
        $this->wizardShowFooterActionsInLastStep = $showFooterActionsInLastStep;

        return $this;
    }

    public function isWizard(): bool
    {
        return count($this->wizardSteps) > 0;
    }

    public function state(array $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function fillUsing(?Closure $callback): static
    {
        $this->fillUsing = $callback;

        return $this;
    }

    public function submitUsing(?Closure $callback): static
    {
        $this->submitUsing = $callback;

        return $this;
    }

    public function submitAction(Closure $callback): static
    {
        $this->submitAction = $callback;

        return $this;
    }

    /** @return array<Field> */
    public function getPreparedFields(): array
    {
        return array_map(
            fn (Field $field) => $field
                ->shareEvaluationParameters($this->getAllEvaluationParameters())
                ->parent($this)
                ->record($this->record)
                ->componentCollection('fields')
                ->model($this->model instanceof Model ? $this->model : null),
            $this->fields
        );
    }

    /** @return array<Field> */
    public function getFields(): array
    {
        return array_values(
            array_filter(
                $this->getPreparedFields(),
                fn (Field $field) => $field->isVisible()
            )
        );
    }

    /** @return array<Step> */
    public function getPreparedSteps(): array
    {
        return array_map(
            fn (Step $step) => $step
                ->shareEvaluationParameters($this->getAllEvaluationParameters())
                ->parent($this)
                ->record($this->record)
                ->model($this->model instanceof Model ? $this->model : null),
            $this->wizardSteps
        );
    }

    /** @return array<Step> */
    public function getSteps(): array
    {
        return array_values(
            array_filter(
                $this->getPreparedSteps(),
                fn (Step $step) => $step->isVisible()
            )
        );
    }

    /**
     * Get all fields - from wizard steps if wizard mode, otherwise regular fields
     *
     * @return array<Field>
     */
    public function getFlattenedFields(): array
    {
        if ($this->isWizard()) {
            $fields = [];
            foreach ($this->getSteps() as $step) {
                $fields = array_merge($fields, $step->getFields());
            }

            return $fields;
        }

        return $this->getFields();
    }

    /**
     * Get all prepared fields - from wizard steps if wizard mode, otherwise regular fields
     *
     * @return array<Field>
     */
    protected function getFlattenedPreparedFields(): array
    {
        if ($this->isWizard()) {
            $fields = [];
            foreach ($this->getPreparedSteps() as $step) {
                $fields = array_merge($fields, $step->getPreparedFields());
            }

            return $fields;
        }

        return $this->getPreparedFields();
    }

    public function getValidationRules(): array
    {
        return collect($this->getFlattenedFields())
            ->mapWithKeys(fn (Field $field) => [$field->getName() => $field->getRules()])
            ->toArray();
    }

    protected function getEvaluationParameters(): array
    {
        return [
            'form' => $this,
            'record' => $this->record,
            'model' => $this->model,
            'data' => $this->state,
        ];
    }

    public function fillForm(): array
    {
        $state = [];

        foreach ($this->getFlattenedPreparedFields() as $field) {
            $state[$field->getName()] = $field->formatState(
                $this->record ? data_get($this->record, $field->getName()) : $field->getDefault()
            );
        }

        return $state;
    }

    protected function getFillUsing(): array
    {
        if (! $this->fillUsing) {
            return [];
        }

        return $this->evaluate($this->fillUsing, $this->getAllEvaluationParameters());
    }

    public function toData(): array
    {
        if (! $this->isVisible()) {
            return [];
        }

        $this->state = $this->getFillUsing();

        $data = [
            'recordId' => $this->record instanceof Model ? $this->record->getKey() : null,
            'fields' => array_map(
                fn (Field $field) => $field->toData(),
                $this->getFields()
            ),
            'state' => $this->state,
            'footerActions' => collect($this->getFooterActions())
                ->filter(fn (Action $action) => $action
                    ->parent($this)
                    ->context($this->context)
                    ->record($this->getRecord())
                    ->args([
                        'data' => $this->state,
                    ])
                    ->isVisible()
                )
                ->map(fn (Action $action) => $action->toData())
                ->values()
                ->toArray(),
        ];

        // Add wizard data if in wizard mode
        if ($this->isWizard()) {
            $data['wizard'] = [
                'steps' => array_map(
                    fn (Step $step) => $step->toData(),
                    $this->getSteps()
                ),
                'skippable' => $this->wizardSkippable,
                'showFooterActionsInLastStep' => $this->wizardShowFooterActionsInLastStep,
            ];
        }

        return array_merge(parent::toData(), $data);
    }

    public function getState(): array
    {
        $attributes = collect($this->getFlattenedFields())
            ->mapWithKeys(fn (Field $field) => [$field->getName() => $field->getLabel()])
            ->toArray();

        return Validator::validate($this->state, $this->getValidationRules(), [], $attributes);
    }

    protected function submit(array $data): void
    {
        $fields = $this->getFlattenedFields();

        $this->state = $data;

        $state = $this->getState();

        DB::transaction(function () use ($state, $fields) {
            foreach ($fields as $field) {
                if (! $field->getDehydrated()) {
                    continue;
                }

                $state[$field->getName()] = $field->dehydrateState($state[$field->getName()] ?? null, $state, []);
            }

            if ($this->submitUsing) {
                $this->evaluate($this->submitUsing, [
                    'data' => $state,
                    'record' => $this->record,
                ]);
            } else {
                if (! $this->model instanceof Model) {
                    throw new RuntimeException('Either define a submitUsing handler or use an Eloquent model.');
                }

                $record = $this->record instanceof Model && $this->record->exists
                    ? $this->record
                    : $this->model->newInstance();

                $record->fill($state);
                $record->save();

                foreach ($fields as $field) {
                    $field->saveState($state[$field->getName()] ?? null, $state);
                }

                Toast::make()
                    ->success()
                    ->send();
            }
        });
    }

    public function getMountableActions(): array
    {
        return array_merge(
            $this->getFooterActions(),
            $this->getFieldActions()
        );
    }

    protected function getFieldActions(): array
    {
        $actions = [];

        // Get fields from wizard steps if in wizard mode, otherwise regular fields
        $fields = $this->isWizard()
            ? $this->getFlattenedFields()
            : $this->getPreparedFields();

        foreach ($fields as $field) {
            if ($field instanceof Schema\ActionsContainer && $field->isVisible()) {
                $actions = array_merge($actions, $field->getMountableActions());
            }
        }

        return $actions;
    }

    protected function getFooterActions(): array
    {
        return array_map(
            fn (Action $action) => $action
                ->shareEvaluationParameters($this->getAllEvaluationParameters())
                ->parent($this)
                ->record($this->record)
                ->model($this->model instanceof Model ? $this->model : null)
                ->componentCollection('actions')
                ->withPublicContext('state', $this->state),
            array_merge($this->footerActions, [
                $this->getSubmitAction(),
            ])
        );
    }

    public function getSubmitAction(): Action
    {
        $action = Action::make('submit')
            ->action(fn (array $data) => $this->submit($data));

        if ($this->submitAction) {
            $this->evaluate($this->submitAction, ['action' => $action]);
        }

        return $action;
    }

    public function getComponent(string $name): Component|ComponentCollection|null
    {
        return match ($name) {
            'fields' => new ComponentCollection($this->getFields()),
            'actions' => new ComponentCollection($this->getMountableActions()),
            default => null,
        };
    }

    protected function getField(string $name): ?Field
    {
        foreach ($this->getFields() as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
    }

    #[Expose]
    public function getFormData(array $state): array
    {
        $stateModifications = [];

        foreach ($this->getFlattenedPreparedFields() as $field) {
            if ($field instanceof Field && $field->isReactive()) {
                $modifications = $field->executeStateUpdate($state);
                $stateModifications = array_merge($stateModifications, $modifications);
            }
        }

        $state = array_merge($state, $stateModifications);

        $this->fillUsing(fn () => $state);

        return $this->toData();
    }

    #[Expose]
    public function validateWizardStep(int $stepIndex, array $state): array
    {
        if (! $this->isWizard()) {
            return ['valid' => false, 'errors' => ['form' => 'Form is not a wizard']];
        }

        $this->state = $state;

        $steps = $this->getSteps();
        if (! isset($steps[$stepIndex])) {
            return ['valid' => false, 'errors' => ['step' => 'Step not found']];
        }

        $step = $steps[$stepIndex];
        $stepFields = $step->getFields();

        // Build validation rules for this step's fields
        $rules = [];
        $attributes = [];
        foreach ($stepFields as $field) {
            $rules[$field->getName()] = $field->getRules();
            $attributes[$field->getName()] = $field->getLabel();
        }

        $validator = Validator::make($state, $rules, [], $attributes);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }
}
