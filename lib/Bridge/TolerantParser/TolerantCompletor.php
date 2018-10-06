<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Generator;
use Microsoft\PhpParser\Node;

interface TolerantCompletor
{
    public function complete(Node $node, string $source, int $offset): Generator;
}
