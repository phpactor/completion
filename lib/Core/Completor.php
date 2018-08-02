<?php

namespace Phpactor\Completion\Core;

use Phpactor\Completion\Core\CanComplete;

interface Completor
{
    public function complete(string $source, int $byteOffset): Response;
}
