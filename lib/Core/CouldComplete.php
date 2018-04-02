<?php

namespace Phpactor\Completion\Core;

use Phpactor\Completion\Core\CanComplete;

interface CouldComplete extends CanComplete
{
    public function couldComplete(string $source, int $offset): bool;
}
