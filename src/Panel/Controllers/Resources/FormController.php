<?php

namespace Hewcode\Hewcode\Panel\Controllers\Resources;

use Hewcode\Hewcode\Forms;
use Hewcode\Hewcode\Panel\Resource;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * @extends ResourceController<Forms\FormDefinition>
 */
abstract class FormController extends ResourceController
{
    /** @var class-string<Forms\FormDefinition> */
    public static string $form;

    protected string $view = 'hewcode/resources/form';

    protected bool $shouldRegisterNavigation = false;

    protected ?Model $record = null;

    protected function mount(): void
    {
        parent::mount();

        $this->resolveRecord();
    }

    protected function resolveRecord(): void
    {
        $hasIdParameter = request()->route()->hasParameter('id');

        if ($hasIdParameter) {
            $id = request()->route()->parameter('id');

            if (! $id) {
                abort(404);
            }

            /** @var class-string<Model> $model */
            $model = $this->getDefinition()->getModelClass();

            $this->record = $model::query()->findOrFail($id);
        }
    }

    protected function getDefinitionClass(): string
    {
        return static::$form;
    }

    protected function getComponents(): array
    {
        return array_merge(parent::getComponents(), ['form']);
    }

    #[Forms\Expose]
    public function form(): Forms\Form
    {
        if (! isset(static::$form) && ! isset($this->definition)) {
            throw new RuntimeException('$form not set on ' . static::class);
        }

        /** @var Forms\FormDefinition $definition */
        $definition = $this->getDefinition();

        if ($definition instanceof Resource) {
            $form = $definition->createForm();
        } else {
            $form = $definition->create('form');
        }

        return $form
            ->model($definition->getModelClass())
            ->record($this->record);
    }
}
