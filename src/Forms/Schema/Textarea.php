<?php

namespace Hewcode\Hewcode\Forms\Schema;

class Textarea extends Field
{
    protected ?int $rows = 4;
    protected ?int $maxLength = null;

    public function rows(int $count): static
    {
        $this->rows = $count;

        return $this;
    }

    protected function getFieldType(): string
    {
        return 'textarea';
    }

    protected function getFieldSpecificData(): array
    {
        return [
            'rows' => $this->rows,
            'maxLength' => $this->maxLength,
        ];
    }
}
