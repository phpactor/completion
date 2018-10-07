<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Generator;
use Microsoft\PhpParser\Node;
use Phpactor\Completion\Core\Suggestion;

interface TolerantCompletor
{
    /**
     * @return Generator & iterable<Suggestion>
     */
    public function complete(Node $node, string $source, int $offset): Generator;
}
