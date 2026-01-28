<?php

namespace Hewcode\Hewcode\Actions\Eloquent;

use Hewcode\Hewcode\Actions\Action;
use Closure;
use Hewcode\Hewcode\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateAction extends Action
{
    public function setUp(): void
    {
        parent::setUp();

        $this->color('success');
        $this->label(__('hewcode::hewcode.actions.create'));
        $this->icon('lucide-plus-circle');
        $this->action($this->getDefaultAction());
    }

    public static function make(string $name = 'create'): static
    {
        return (new static())->name($name);
    }

    protected function getDefaultAction(): Closure
    {
        return function (array $data, Model $model, Action $action) {
            return DB::transaction(function () use ($data, $model, $action) {
                Toast::make()
                    ->success()
                    ->send();

                $action->close();

                return $model->newQuery()->create($data);
            });
        };
    }
}
