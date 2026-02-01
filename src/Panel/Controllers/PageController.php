<?php

namespace Hewcode\Hewcode\Panel\Controllers;

use Hewcode\Hewcode\Actions;
use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Panel\Navigation;
use Hewcode\Hewcode\Props;
use Inertia\Inertia;
use Inertia\Response;

abstract class PageController
{
    protected string $view;

    protected string $icon = 'lucide-file-text';

    protected ?int $navigationSort = null;

    protected bool $shouldRegisterNavigation = true;

    protected function mount(): void
    {
        //
    }

    public function __invoke(): Response
    {
        $this->mount();

        return Inertia::render(
            $this->getView(),
            $this->props(
                Props\Props::make($this)
                    ->components($this->getComponents())
                    ->data([
                        'title' => $this->getTitle(),
                    ])
            )
        );
    }

    protected function props(Props\Props $props): Props\Props
    {
        return $props;
    }

    protected function getComponents(): array
    {
        return ['headerActions'];
    }

    protected function getView(): string
    {
        return $this->view;
    }

    public function getTitle(): string
    {
        return str($this->singularLabel())->title();
    }

    public function singularLabel(): string
    {
        return str(static::class)
            ->afterLast('\\')
            ->before('Controller')
            ->replace('-', ' ')
            ->kebab()
            ->title()
            ->lower();
    }

    public function pluralLabel(): string
    {
        return str($this->singularLabel())->plural();
    }

    public function getRoutePath(): string
    {
        return str(static::class)
            ->afterLast('\\')
            ->before('Controller')
            ->kebab()
            ->prepend('/');
    }

    public function getRouteName(): string
    {
        return str(static::class)
            ->afterLast('\\')
            ->before('Controller')
            ->kebab();
    }

    #[Actions\Expose]
    public function headerActions(): Actions\Actions
    {
        return Actions\Actions::make($this->getHeaderActions())->visible();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function registerNavigation(Navigation\Navigation $navigation): void
    {
        if (! $this->getShouldRegisterNavigation()) {
            return;
        }

        $navigation->item(
            Navigation\NavigationItem::make()
                ->url(fn () => Hewcode::route($this->getRouteName(), panel: $navigation->getPanel()))
                ->label($this->getNavigationTitle())
                ->icon($this->getNavigationIcon())
                ->order($this->navigationSort)
        );
    }

    protected function getNavigationTitle(): string
    {
        return $this->getTitle();
    }

    protected function getNavigationIcon(): string
    {
        return $this->icon;
    }

    protected function getShouldRegisterNavigation(): bool
    {
        return $this->shouldRegisterNavigation;
    }

    public function panels(): array|true
    {
        return [Hewcode::config()->getDefaultPanel()];
    }
}
