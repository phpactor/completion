<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Generator;
use Microsoft\PhpParser\Node;
use Phpactor\Completion\Bridge\TolerantParser\Qualifier\Qualifier;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Suggestions;

interface TolerantCompletor
{
    public function complete(Node $node, string $source, int $offset): Generator;
}
