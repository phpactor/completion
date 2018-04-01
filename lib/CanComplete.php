<?php

namespace Phpactor\Completion;

interface CanComplete
{
    public function complete(string $source, int $offset): Response;
}
