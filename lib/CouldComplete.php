<?php

namespace Phpactor\Completion;

interface CouldComplete extends CanComplete
{
    public function couldComplete(string $source, int $offset): bool;
}
