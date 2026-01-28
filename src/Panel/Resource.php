<?php

namespace Hewcode\Hewcode\Panel;

use Hewcode\Hewcode\Actions\Action;
use Hewcode\Hewcode\Definition;
use Hewcode\Hewcode\Forms;
use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Lists;
use Hewcode\Hewcode\Panel\Controllers\Resources\EditController;
use Hewcode\Hewcode\Panel\Controllers\Resources\ResourceController;

abstract class Resource extends Definition
{
    /** @var class-string<Lists\ListingDefinition> */
    public static string $listing;

    public function name(): string
    {
        return str(static::class)
            ->afterLast('\\')
            ->beforeLast('Resource')
            ->plural()
            ->kebab();
    }

    public function createListing(): Lists\Listing
    {
        /** @var Lists\ListingDefinition $definition */
        $definition = isset(static::$listing)
            ? app(static::$listing)
            : null;

        if (! $definition) {
            $definition = (new Lists\ListingDefinition())
                ->formDefinition($this->createFormDefinition())
                ->model($this->getModelClass());
        }

        $listing = $definition->create('listing');

        return $this
            ->listing($listing)
            ->touchActionsUsing(function (Action $action) {
                foreach ($this->getPageControllers() as $pageController) {
                    if ($action->getName() === 'edit' && $pageController instanceof EditController) {
                        $action->url(fn () => $pageController->getUrl(['id' => $action->getRecord()?->getKey()]));
                    } elseif ($action->getName() === 'create' && $pageController instanceof EditController) {
                        $action->url(fn () => $pageController->getUrl());
                    }
                }

                return $action;
            });
    }

    public function createFormDefinition(): Forms\FormDefinition
    {
        return (new Forms\FormDefinition())
            ->model($this->getModelClass())
            ->touch(fn (Forms\Form $form) => $this->form($form));
    }

    public function createForm(): Forms\Form
    {
        $formDefinition = $this->createFormDefinition();

        $form = $formDefinition->create('form');

        return $this->form($form);
    }

    /**
     * @return (class-string<ResourceController>)[]
     */
    public function pages(): array
    {
        return [
            Controllers\Resources\IndexController::page(),
        ];
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form;
    }

    public function listing(Lists\Listing $listing): Lists\Listing
    {
        return $listing;
    }

    public function panels(): array
    {
        return [Hewcode::config()->getDefaultPanel()];
    }

    public function getPageControllers(): array
    {
        return array_map(function (Page $page) {
            return $page->resolve($this);
        }, $this->pages());
    }
}
