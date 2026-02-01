<?php

namespace Hewcode\Hewcode\Panel\Controllers\Resources;

use Hewcode\Hewcode\Actions;
use Hewcode\Hewcode\Definition;
use Hewcode\Hewcode\Hewcode;
use Hewcode\Hewcode\Lists\ListingDefinition;
use Hewcode\Hewcode\Panel\Controllers\PageController;
use Hewcode\Hewcode\Panel\Page;
use Hewcode\Hewcode\Panel\Resource;
use ReflectionClass;
use RuntimeException;

/**
 * @template T of Definition
 */
abstract class ResourceController extends PageController
{
    protected ?Definition $definition = null;

    protected ?Page $page = null;

    public function definition(Definition $definition): static
    {
        $this->definition = $definition;

        return $this;
    }

    public function withPage(Page $page): static
    {
        $this->page = $page;

        return $this;
    }

    abstract protected function getDefinitionClass(): ?string;

    public function name(): string
    {
        $definition = $this->getDefinition();

        if ($definition instanceof Resource) {
            return $definition->name();
        }

        return str(static::class)
            ->afterLast('\\')
            ->beforeLast('Controller')
            ->after($this->getControllerNamePrefix())
            ->plural()
            ->kebab();
    }

    public function singularLabel(): string
    {
        return str($this->name())
            ->headline()
            ->lower()
            ->singular();
    }

    public function pluralLabel(): string
    {
        return str($this->name())
            ->headline()
            ->plural();
    }

    public function getControllerNamePrefix(): string
    {
        return '';
    }

    public function getDefinition(): Definition
    {
        if ($this->definition === null) {
            $this->definition = app($this->getDefinitionClass());
        }

        return $this->definition;
    }

    public function getTitle(): string
    {
        return $this->page?->getTitle() ?? str($this->name())
            ->headline()
            ->plural();
    }

    public function getRoutePath(): string
    {
        return str($this->name())
            ->prepend('/')
            ->append($this->getRoutePathSuffix());
    }

    public function getRoutePathSuffix(): string
    {
        return '';
    }

    public function getRouteName(): string
    {
        return str($this->name())
            ->append($this->getRouteNameSuffix());
    }

    public function getRouteNameSuffix(): string
    {
        return '';
    }

    #[Actions\Expose]
    public function headerActions(): Actions\Actions
    {
        $definition = $this->getDefinition();

        $formDefinition = null;

        if ($definition instanceof ListingDefinition) {
            $formDefinition = $definition->getFormDefinition();
        } elseif ($definition instanceof Resource) {
            $formDefinition = $definition->createFormDefinition();
        }

        return parent::headerActions()
            ->usingForm($formDefinition)
            ->model($definition->getModelClass());
    }

    protected function getHeaderActions(): array
    {
        return array_merge(parent::getHeaderActions(), $this->page?->getHeaderActions() ?? []);
    }

    public function panels(): array
    {
        if ($this->definition instanceof Resource) {
            return $this->definition->panels();
        }

        return parent::panels();
    }

    public static function page(): Page
    {
        $reflection = new ReflectionClass(static::class);

        if ($reflection->isAbstract()) {
            throw new RuntimeException('Cannot use abstract controller '.static::class.' as a resource page.');
        }

        return new Page(static::class);
    }

    public function getUrl(array $parameters = [], bool $absolute = true, ?string $panel = null): string
    {
        return Hewcode::route(
            $this->getRouteName(),
            $parameters,
            $absolute,
            $panel ?? $this->panels()[0] ?? null
        );
    }
}
