<?php

namespace Phpactor\Completion\Completor;

use Phpactor\Completion\CouldComplete;
use Phpactor\Completion\Response;

class LocalVariableCompletor implements CouldComplete
{
    public function complete(string $source, int $offset): Response
    {
    }

    public function couldComplete(string $source, int $offset): bool
    {
    }
}
