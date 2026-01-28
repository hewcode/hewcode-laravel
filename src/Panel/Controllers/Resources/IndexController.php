<?php

namespace Hewcode\Hewcode\Panel\Controllers\Resources;

use Hewcode\Hewcode\Lists;
use Hewcode\Hewcode\Panel\Resource;
use RuntimeException;

/**
 * @extends ResourceController<Lists\Listing>
 */
class IndexController extends ResourceController
{
    /** @var class-string<Lists\ListingDefinition> */
    public static string $listing;

    protected string $view = 'hewcode/resources/index';

    protected function getDefinitionClass(): string
    {
        return static::$listing;
    }

    public function getControllerNamePrefix(): string
    {
        return 'List';
    }

    protected function getComponents(): array
    {
        return array_merge(parent::getComponents(), ['listing']);
    }

    #[Lists\Expose]
    public function listing(): Lists\Listing
    {
        if (! isset(static::$listing) && ! isset($this->definition)) {
            throw new RuntimeException('$listing not set on ' . static::class);
        }

        /** @var Lists\ListingDefinition|Resource $definition */
        $definition = $this->getDefinition();

        if ($definition instanceof Resource) {
            return $definition->createListing();
        }

        return $definition->create('listing');
    }

    public function getRouteNameSuffix(): string
    {
        return '.index';
    }
}
