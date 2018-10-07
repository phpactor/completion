<?php

namespace Phpactor\Completion\Core;

use Generator;

interface Completor
{
    /**
     * @return Generator & iterable<Suggestion>
     */
    public function complete(string $source, int $byteOffset): Generator;
}
