<?php

namespace Hewcode\Hewcode\Forms\Schema\Wizard;

use Closure;
use Hewcode\Hewcode\Concerns;
use Hewcode\Hewcode\Contracts;
use Hewcode\Hewcode\Forms\Schema\Field;
use Hewcode\Hewcode\Support\Component;
use Illuminate\Database\Eloquent\Model;

class Step extends Component implements Contracts\HasVisibility, Contracts\HasRecord
{
    use Concerns\HasLabel;
    use Concerns\HasVisibility;
    use Concerns\HasRecord;
    use Concerns\HasModel;

    /** @var array<Field> */
    protected array $fields = [];
    protected ?string $description = null;
    protected ?string $icon = null;

    public function __construct()
    {
        $this->setUp();
    }

    protected function setUp(): void
    {
        //
    }

    public static function make(string $name): static
    {
        return (new static())->name($name);
    }

    public function schema(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /** @return array<Field> */
    public function getPreparedFields(): array
    {
        return array_map(
            fn (Field $field) => $field
                ->shareEvaluationParameters($this->getAllEvaluationParameters())
                ->parent($this->getParent())
                ->record($this->record)
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

    protected function getEvaluationParameters(): array
    {
        return [
            'step' => $this,
            'record' => $this->record,
            'model' => $this->model,
        ];
    }

    public function toData(): array
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'icon' => $this->getIcon(),
            'fields' => array_map(
                fn (Field $field) => $field->toData(),
                $this->getFields()
            ),
        ];
    }
}
