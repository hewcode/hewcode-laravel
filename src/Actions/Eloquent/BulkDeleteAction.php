<?php

namespace Hewcode\Hewcode\Actions\Eloquent;

use Hewcode\Hewcode\Actions\BulkAction;
use Closure;
use Hewcode\Hewcode\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkDeleteAction extends BulkAction
{
    protected bool $shouldForceDelete = false;

    public function setUp(): void
    {
        parent::setUp();

        $this->color('danger');
        $this->label(__('hewcode::hewcode.actions.delete'));
        $this->requiresConfirmation();
        $this->action($this->getDefaultAction());
    }

    public static function make(string $name = 'delete'): static
    {
        return (new static())->name($name);
    }

    public function forceDelete(): static
    {
        $this->shouldForceDelete = true;
        $this->name('force_delete');

        return $this;
    }

    protected function getDefaultAction(): Closure
    {
        return function (Collection $records) {
            return DB::transaction(function () use ($records) {
                Toast::make()
                    ->success()
                    ->send();

                if ($this->shouldForceDelete) {
                    return $records->each(fn (Model $record) => $record->forceDelete());
                }

                return $records->each(fn (Model $record) => $record->delete());
            });
        };
    }
}
