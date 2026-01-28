<?php

namespace Hewcode\Hewcode\Actions\Eloquent;

use Hewcode\Hewcode\Actions\BulkAction;
use Closure;
use Hewcode\Hewcode\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkRestoreAction extends BulkAction
{
    public function setUp(): void
    {
        parent::setUp();

        $this->color('warning');
        $this->label(__('hewcode::hewcode.actions.restore'));
        $this->action($this->getDefaultAction());
    }

    public static function make(string $name = 'restore'): static
    {
        return (new static())->name($name);
    }

    protected function getDefaultAction(): Closure
    {
        return function (Collection $records) {
            return DB::transaction(function () use ($records) {
                Toast::make()
                    ->success()
                    ->send();

                return $records->each(fn (Model $record) => $record->restore());
            });
        };
    }
}
