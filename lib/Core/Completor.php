<?php

namespace Phpactor\Completion\Core;

use Generator;
use Phpactor\Completion\Core\CanComplete;

interface Completor
{
    /**
     * @return Generator<Suggestion>
     */
    public function complete(string $source, int $byteOffset): Generator;
}
