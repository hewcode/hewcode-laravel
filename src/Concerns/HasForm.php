<?php

namespace Hewcode\Hewcode\Concerns;

use Hewcode\Hewcode\Actions\Actions;
use Hewcode\Hewcode\Forms\Form;
use Hewcode\Hewcode\Forms\FormDefinition;
use Hewcode\Hewcode\Lists\Listing;

trait HasForm
{
    use HasFormSchema;

    public function getForm(): Form
    {
        $form = $this->getFormDefinition()?->create() ?? Form::make();

        if (! empty($schema = $this->getFormSchema())) {
            $form->schema($schema);
        }

        return $form
            ->parent($this)
            ->record($record = $this->getRecord())
            ->model($record ?? $this->getModel())
            ->visible($this->isVisible())
            ->shareEvaluationParameters($this->getAllEvaluationParameters())
            ->submitUsing($this->action);
    }

    protected function getFormDefinition(): ?FormDefinition
    {
        $parent = $this->getParent();

        if (($parent instanceof Listing || $parent instanceof Actions) && ($form = $parent->getFormDefinition())) {
            return $form;
        }

        return null;
    }
}
