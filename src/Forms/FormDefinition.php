<?php

namespace Hewcode\Hewcode\Forms;

use Closure;
use Hewcode\Hewcode\Definition;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class FormDefinition extends Definition
{
    protected ?Closure $touchUsing = null;

    public function touch(?Closure $touchUsing): static
    {
        $this->touchUsing = $touchUsing;

        return $this;
    }

    public function default(Form $form): Form
    {
        return $form;
    }

    public function create(string $name = '', string $context = 'default'): Form
    {
        $form = Form::make()->name($name)->context($this->context);

        if (isset($this->model) && is_a($this->model, Model::class, true)) {
            $form->model($this->model);
        }

        if ($this->touchUsing) {
            ($this->touchUsing)($form);
        }

        return match ($context) {
            'default' => $this->default($form),
            default => throw new RuntimeException("Unknown form context: $context"),
        };
    }

    public static function make(string $name, string $context = 'default'): Form
    {
        return app(static::class)->create($name, $context);
    }
}
