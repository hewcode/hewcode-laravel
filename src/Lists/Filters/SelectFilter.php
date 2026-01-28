<?php

namespace Hewcode\Hewcode\Lists\Filters;

use Hewcode\Hewcode\Concerns\HasOptions;
use Hewcode\Hewcode\Forms;

class SelectFilter extends Filter
{
    use HasOptions;

    public string $type = 'select';

    public function getFormSchema(): array
    {
        return [
            Forms\Schema\Select::make($this->name)
                ->label($this->label)
                ->options($this->options)
                ->multiple($this->multiple)
                ->searchable($this->searchable)
                ->searchResultsUsing($this->searchResultsUsing)
                ->preload($this->preloadLimit)
                ->relationship($this->relationshipName, $this->relationshipTitleColumn, $this->relationshipModifyQueryUsing),
        ];
    }
}
