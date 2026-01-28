<?php

namespace Hewcode\Hewcode\Props;

use Hewcode\Hewcode\Contracts\HasOwnerRecord;
use Hewcode\Hewcode\Contracts\HasRecord;
use Hewcode\Hewcode\Contracts\ResolvesRecords;
use Hewcode\Hewcode\Support\Container;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionException;
use Hewcode\Hewcode\Lists\Listing;
use Hewcode\Hewcode\Lists\Expose as ListingExpose;
use Hewcode\Hewcode\Actions\Expose as ActionsExpose;
use Hewcode\Hewcode\Forms\Expose as FormsExpose;
use Hewcode\Hewcode\Widgets\Expose as WidgetsExpose;
use Illuminate\Contracts\Support\Arrayable;
use RuntimeException;
use InvalidArgumentException;

class Props implements Arrayable
{
    protected array $data = [];
    protected ?object $controller = null;
    protected array $componentNames = [];
    protected ?Model $record = null;

    public static function make(?object $controller, array $data = []): static
    {
        return new static($controller, $data);
    }

    public function __construct(?object $controller, array $data = [])
    {
        $this->controller = $controller;
        $this->data = $data;
    }

    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function appendData(array $data): static
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function components(array $names): static
    {
        $this->componentNames = $names;

        return $this;
    }

    public function component(string $name): static
    {
        $this->componentNames[] = $name;

        return $this;
    }

    public static function for(?object $controller): static
    {
        return new static($controller);
    }

    public function record(?Model $record): static
    {
        $this->record = $record;

        return $this;
    }

    public function toArray(): array
    {
        $discovered = $this->discoverComponents();

        return array_merge($this->data, $discovered);
    }

    protected function discoverComponents(): array
    {
        if ($this->controller === null) {
            throw new InvalidArgumentException('Controller must be set via components() method or forController()');
        }

        if (empty($this->componentNames)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($this->controller);
        } catch (ReflectionException) {
            throw new RuntimeException(
                sprintf('Unable to reflect controller class: %s', get_class($this->controller))
            );
        }

        $discovered = [];
        $listingComponents = [];

        // First pass: identify all components and collect listings
        foreach ($this->componentNames as $componentName) {
            // Try to find a method with this name
            if (! $reflection->hasMethod($componentName)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Method %s::%s() does not exist',
                        get_class($this->controller),
                        $componentName
                    )
                );
            }

            $method = $reflection->getMethod($componentName);

            // Method must be public
            if (! $method->isPublic()) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Method %s::%s() must be public',
                        get_class($this->controller),
                        $componentName
                    )
                );
            }

            // Method must have either Lists\Expose, Actions\Expose, Forms\Expose, or Widgets\Expose attribute
            $listingAttributes = $method->getAttributes(ListingExpose::class);
            $actionsAttributes = $method->getAttributes(ActionsExpose::class);
            $formsAttributes = $method->getAttributes(FormsExpose::class);
            $widgetsAttributes = $method->getAttributes(WidgetsExpose::class);

            if (empty($listingAttributes) && empty($actionsAttributes) && empty($formsAttributes) && empty($widgetsAttributes)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Method %s::%s() must have either #[Lists\\Expose], #[Actions\\Expose], #[Forms\\Expose], or #[Widgets\\Expose] attribute',
                        get_class($this->controller),
                        $componentName
                    )
                );
            }

            // Check for method name conflicts with existing data
            if (isset($this->data[$componentName]) || isset($discovered[$componentName])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Key conflict: "%s" already exists in data or discovered components. Method %s::%s() conflicts with existing key.',
                        $componentName,
                        get_class($this->controller),
                        $componentName
                    )
                );
            }

            // Track if this is a listing component
            if (!empty($listingAttributes)) {
                $listingComponents[] = $componentName;
            }
        }

        // Determine if we should auto-scope listings (when there are multiple)
        $shouldAutoScope = count($listingComponents) > 1;

        // Second pass: create and configure components
        foreach ($this->componentNames as $componentName) {
            $method = $reflection->getMethod($componentName);

            // Call the method to get the component
            $component = $method->invoke($this->controller);

            if (! $component instanceof Container) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Method %s::%s() must return an instance of Container (List, Actions, or Form)',
                        get_class($this->controller),
                        $componentName
                    )
                );
            }

            $component->name($componentName);

            // Auto-scope listings if there are multiple and the listing doesn't already have a scope
            if ($shouldAutoScope && $component instanceof Listing && ! $component->getRequestScope()) {
                $component->scope($componentName);
            }

            if ($component instanceof ResolvesRecords && $this->record) {
                $component->model($this->record);
            }

            if ($component instanceof HasRecord && $this->record) {
                $component->record($this->record);
            }

            if ($component instanceof HasOwnerRecord && $this->record) {
                $component->ownerRecord($this->record);
            }

            // Convert to data format
            $discovered[$componentName] = $component->toData();
        }

        return $discovered;
    }
}
