<?php

namespace Hewcode\Hewcode\Support;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Expose
{
    public function __construct(public ?string $key = null)
    {
    }
}
