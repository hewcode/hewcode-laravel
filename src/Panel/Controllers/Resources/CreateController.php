<?php

namespace Hewcode\Hewcode\Panel\Controllers\Resources;

class CreateController extends FormController
{
    public function getRoutePathSuffix(): string
    {
        return '/create';
    }

    public function getRouteNameSuffix(): string
    {
        return '.create';
    }

    public function getControllerNamePrefix(): string
    {
        return 'Create';
    }
}
