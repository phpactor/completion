<?php

namespace Phpactor\Completion\Bridge\TolerantParser\ReferenceFinder;

use Generator;
use Microsoft\PhpParser\Node;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor as CoreNameSearcherCompletor;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

class NameSearcherCompletor extends CoreNameSearcherCompletor implements TolerantCompletor
{
    /**
     * {@inheritDoc}
     */
    public function complete(Node $node, TextDocument $source, ByteOffset $offset): Generator
    {
        $suggestions = $this->completeName($node->getText(), $source->uri());

        yield from $suggestions;

        return $suggestions->getReturn();
    }
}
