<?php

namespace Hewcode\Hewcode\Contracts;

interface HasRecord
{
    public function record(mixed $record): static;

    public function getRecord(): mixed;
}
