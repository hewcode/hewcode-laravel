<?php

namespace Hewcode\Hewcode\Forms\Schema;

use Hewcode\Hewcode\Concerns\HasOptions;

class Select extends Field
{
    use HasOptions;

    protected function getFieldType(): string
    {
        return 'select';
    }

    public function getFieldSpecificData(): array
    {
        return [
            'options' => $this->getOptions(),
            'multiple' => $this->getMultiple(),
            'searchable' => $this->getSearchable(),
        ];
    }
}
