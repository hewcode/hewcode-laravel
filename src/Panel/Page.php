<?php

namespace Hewcode\Hewcode\Panel;

use Hewcode\Hewcode\Panel\Controllers\Resources\ResourceController;

class Page
{
    protected array $headerActions = [];
    protected ?string $title = null;

    public function __construct(
        /** @var class-string<ResourceController> */
        protected string $controller
    ) {
    }

    public function headerActions(array $actions): static
    {
        $this->headerActions = $actions;

        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getHeaderActions(): array
    {
        return $this->headerActions;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function resolve(Resource $resource): ResourceController
    {
        return app($this->controller)
            ->withPage($this)
            ->definition($resource);
    }
}
