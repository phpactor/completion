<?php

namespace Phpactor\Completion\Bridge\TolerantParser\ReferenceFinder;

use Generator;
use Microsoft\PhpParser\Node;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor as CoreNameSearcherCompletor;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

final class NameSearcherCompletor implements TolerantCompletor
{
    /**
     * @var CoreNameSearcherCompletor
     */
    private $nameCompletor;

    public function __construct(CoreNameSearcherCompletor $nameCompletor)
    {
        $this->nameCompletor = $nameCompletor;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(Node $node, TextDocument $source, ByteOffset $offset): Generator
    {
        $suggestions = $this->nameCompletor->complete($source, $offset, $node->getText());

        yield from $suggestions;

        return $suggestions->getReturn();
    }
}
