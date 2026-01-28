<?php

namespace Hewcode\Hewcode\Contracts;

use Hewcode\Hewcode\Actions\Action;

interface MountsActions
{
    function getMountableActions(): array;

    public function mountAction(Action $action, array $args): mixed;
}
