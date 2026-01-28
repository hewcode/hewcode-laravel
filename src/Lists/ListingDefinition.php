<?php

namespace Hewcode\Hewcode\Lists;

use Hewcode\Hewcode\Definition;
use Hewcode\Hewcode\Forms;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ListingDefinition extends Definition
{
    /** @var class-string<Forms\FormDefinition>|null */
    protected ?string $form = null;

    protected ?Forms\FormDefinition $formInstance = null;

    public function formDefinition(Forms\FormDefinition $form): static
    {
        $this->formInstance = $form;

        return $this;
    }

    public function default(Listing $listing): Listing
    {
        return $listing;
    }

    public function create(string $name, string $context = 'default'): Listing
    {
        $listing = Listing::make()->name($name)->context($this->context);

        if (isset($this->model) && is_a($this->model, Model::class, true)) {
            /** @var class-string<Model> $model */
            $model = $this->model;

            if ($this->form) {
                $listing->usingForm($this->getFormDefinition());
            }

            $listing
                ->query($model::query());
        }

        return match ($context) {
            'default' => $this->default($listing),
            default => throw new RuntimeException("Unknown listing context: $context"),
        };
    }

    public static function make(string $name, string $context = 'default'): Listing
    {
        return app(static::class)->create($name, $context);
    }

    public function getFormDefinition(): ?Forms\FormDefinition
    {
        if ($this->formInstance) {
            return $this->formInstance;
        }

        if ($this->form) {
            return $this->formInstance = app($this->form);
        }

        return null;
    }
}
