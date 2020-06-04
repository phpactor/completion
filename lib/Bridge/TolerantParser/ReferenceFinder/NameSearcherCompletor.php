<?php

namespace Phpactor\Completion\Bridge\TolerantParser\ReferenceFinder;

use Generator;
use Microsoft\PhpParser\Node;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

class NameSearcherCompletor implements TolerantCompletor
{
    /**
     * @var NameSearcher
     */
    private $searcher;

    public function __construct(NameSearcher $searcher)
    {
        $this->searcher = $searcher;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(Node $node, TextDocument $source, ByteOffset $offset): Generator
    {
        $query = $node->getText();

        foreach ($this->searcher->search($query) as $result) {
            yield Suggestion::createWithOptions($result->name()->head(), [
                'short_description' => $result->name()->__toString(),
                'type' => $this->suggestionType($result),
                'class_import' => $this->classImport($result),
                'name_import' => $result->name()->__toString(),
            ]);
        }

        return true;
    }

    private function suggestionType(NameSearchResult $result): ?string
    {
        if ($result->type()->isClass()) {
            return Suggestion::TYPE_CLASS;
        }

        if ($result->type()->isFunction()) {
            return Suggestion::TYPE_FUNCTION;
        }

        return null;
    }

    private function classImport(NameSearchResult $result): ?string
    {
        if ($result->type()->isClass()) {
            return $result->name()->__toString();
        }

        return null;
    }
}
