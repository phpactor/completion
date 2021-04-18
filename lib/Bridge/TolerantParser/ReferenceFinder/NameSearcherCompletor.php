<?php

namespace Phpactor\Completion\Bridge\TolerantParser\ReferenceFinder;

use Generator;
use Microsoft\PhpParser\Node;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor as CoreNameSearcherCompletor;
use Phpactor\Completion\Core\DocumentPrioritizer\DocumentPrioritizer;
use Phpactor\Completion\Core\Formatter\NameSearchResultFunctionSnippetFormatter;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentUri;

class NameSearcherCompletor extends CoreNameSearcherCompletor implements TolerantCompletor
{
    /**
     * @var NameSearchResultFunctionSnippetFormatter
     */
    private $snippetFormatter;

    public function __construct(
        NameSearcher $nameSearcher,
        NameSearchResultFunctionSnippetFormatter $snippetFormatter,
        DocumentPrioritizer $prioritizer = null
    ) {
        parent::__construct($nameSearcher, $prioritizer);

        $this->snippetFormatter = $snippetFormatter;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(Node $node, TextDocument $source, ByteOffset $offset): Generator
    {
        $suggestions = $this->completeName($node->getText(), $source->uri());

        yield from $suggestions;

        return $suggestions->getReturn();
    }

    protected function createSuggestionOptions(NameSearchResult $result, ?TextDocumentUri $sourceUri = null): array
    {
        $suggestions = parent::createSuggestionOptions($result, $sourceUri);

        if ($this->snippetFormatter->canFormat($result)) {
            return array_merge(
                $suggestions,
                [
                    'snippet' => $this->snippetFormatter->format($result)
                ]
            );
        }

        return $suggestions;
    }
}
