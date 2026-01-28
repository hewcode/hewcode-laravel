<?php

namespace Hewcode\Hewcode\Contracts;

interface HasOwnerRecord
{
    public function ownerRecord(mixed $record): static;

    public function getOwnerRecord(): mixed;
}
