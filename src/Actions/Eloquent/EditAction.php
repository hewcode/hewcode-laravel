<?php

namespace Hewcode\Hewcode\Actions\Eloquent;

use Closure;
use Hewcode\Hewcode\Actions\Action;
use Hewcode\Hewcode\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditAction extends Action
{
    public function setUp(): void
    {
        parent::setUp();

        $this->color('primary');
        $this->label(__('hewcode::hewcode.actions.edit'));
        $this->icon('lucide-pencil');
        $this->action($this->getDefaultAction());
    }

    public static function make(string $name = 'edit'): static
    {
        return (new static)->name($name);
    }

    protected function getDefaultAction(): Closure
    {
        return function (Model $record, Action $action, array $data = []) {
            return DB::transaction(function () use ($data, $record, $action) {
                Toast::make()
                    ->success()
                    ->send();

                $action->close();

                $record->update($data);

                return $record;
            });
        };
    }

    public function getMountsModal(): bool
    {
        return true;
    }
}
