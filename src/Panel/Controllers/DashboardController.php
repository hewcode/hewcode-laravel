<?php

namespace Hewcode\Hewcode\Panel\Controllers;

use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Widgets;

class DashboardController extends PageController
{
    protected string $view = 'hewcode/dashboard';

    protected string $icon = 'lucide-layout-grid';

    protected ?int $navigationSort = 0;

    public function getRoutePath(): string
    {
        return '/';
    }

    public function panels(): array|true
    {
        return true;
    }

    protected function getComponents(): array
    {
        return ['headerActions', 'widgets'];
    }

    #[Widgets\Expose]
    public function widgets(): Widgets\Widgets
    {
        return Widgets\Widgets::make([
            $this->hewcodeInfoWidget(),
        ])->visible();
    }

    protected function hewcodeInfoWidget(): Widgets\InfoWidget
    {
        return Widgets\InfoWidget::make('hewcode_info')
            ->heading('Hewcode')
            ->description(Hewcode::VERSION);
    }

    public function getTitle(): string
    {
        return __('hewcode::hewcode.dashboard.title');
    }
}
