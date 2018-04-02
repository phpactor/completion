<?php

namespace Phpactor\Completion\Core;

interface CanComplete
{
    public function complete(string $source, int $offset): Response;
}
