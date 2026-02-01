<?php

namespace Hewcode\Hewcode\Forms\Schema;

class TextInput extends Field
{
    protected ?int $maxLength = null;

    protected ?string $type = 'text';

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    protected function getFieldType(): string
    {
        return 'text-input';
    }

    protected function getFieldSpecificData(): array
    {
        return [
            'inputType' => $this->type,
            'maxLength' => $this->maxLength,
        ];
    }
}
