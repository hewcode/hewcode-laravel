<?php

namespace Hewcode\Hewcode\Actions\Eloquent;

use Hewcode\Hewcode\Actions\Action;
use Closure;
use Hewcode\Hewcode\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RestoreAction extends Action
{
    public function setUp(): void
    {
        parent::setUp();

        $this->color('warning');
        $this->label(__('hewcode::hewcode.actions.restore'));
        $this->hidden(fn (Model $record) => ! method_exists($record, 'restore') || !$record->trashed());
        $this->action($this->getDefaultAction());
    }

    public static function make(string $name = 'restore'): static
    {
        return (new static())->name($name);
    }

    protected function getDefaultAction(): Closure
    {
        return function (array $data, Model $record) {
            return DB::transaction(function () use ($record) {
                Toast::make()
                    ->success()
                    ->send();

                return $record->restore();
            });
        };
    }
}
