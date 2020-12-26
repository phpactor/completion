<?php

namespace Phpactor\Completion\Core\Completor;

use Generator;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

final class CoreNameSearcherCompletor implements NameSearcherCompletor
{
    /**
     * @var NameSearcher
     */
    protected $nameSearcher;

    public function __construct(NameSearcher $nameSearcher)
    {
        $this->nameSearcher = $nameSearcher;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(
        TextDocument $source,
        ByteOffset $byteOffset,
        string $name = null
    ): Generator {
        if (!$name) {
            return true;
        }

        foreach ($this->nameSearcher->search($name) as $result) {
            $fqcn = $result->name();

            yield Suggestion::createWithOptions($fqcn->head(), [
                'type' => $this->suggestionType($result),
                'short_description' => (string) $fqcn,
                'name_import' => (string) $fqcn,
            ]);
        }

        return true;
    }

    protected function suggestionType(NameSearchResult $result): ?string
    {
        if ($result->type()->isClass()) {
            return Suggestion::TYPE_CLASS;
        }

        if ($result->type()->isFunction()) {
            return Suggestion::TYPE_FUNCTION;
        }

        return null;
    }
}
