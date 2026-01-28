<?php

namespace Hewcode\Hewcode\Actions\Eloquent;

use Hewcode\Hewcode\Actions\Action;
use Closure;
use Hewcode\Hewcode\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeleteAction extends Action
{
    protected bool $shouldForceDelete = false;

    public function setUp(): void
    {
        parent::setUp();

        $this->color('danger');
        $this->label(__('hewcode::hewcode.actions.delete'));
        $this->icon('lucide-trash');
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
        return function (Model $record) {
            return DB::transaction(function () use ($record) {
                Toast::make()
                    ->success()
                    ->send();

                if ($this->shouldForceDelete) {
                    return $record->forceDelete();
                }

                return $record->delete();
            });
        };
    }
}
