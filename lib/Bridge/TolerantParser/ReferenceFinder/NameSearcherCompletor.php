<?php

namespace Phpactor\Completion\Bridge\TolerantParser\ReferenceFinder;

use Generator;
use Microsoft\PhpParser\Node;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor as CoreNameSearcherCompletor;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

class NameSearcherCompletor implements TolerantCompletor
{
    use CoreNameSearcherCompletor;

    /**
     * @var NameSearcher
     */
    private $nameSearcher;

    public function __construct(NameSearcher $nameSearcher)
    {
        $this->nameSearcher = $nameSearcher;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(Node $node, TextDocument $source, ByteOffset $offset): Generator
    {
        $suggestions = $this->completeName($node->getText());

        yield from $suggestions;

        return $suggestions->getReturn();
    }

    protected function getSearcher(): NameSearcher
    {
        return $this->nameSearcher;
    }
}