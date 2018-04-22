<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Microsoft\PhpParser\Node;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestions;

interface TolerantCompletor
{
    public function complete(Node $node, string $source, int $offset): Response;
}
