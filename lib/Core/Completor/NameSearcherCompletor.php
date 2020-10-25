<?php

namespace Phpactor\Completion\Core\Completor;

use Generator;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;

trait NameSearcherCompletor
{
    abstract protected function getSearcher(): NameSearcher;

    /**
     * @return Generator<Suggestion>
     */
    protected function completeName(string $name): Generator
    {
        foreach ($this->getSearcher()->search($name) as $result) {
            yield Suggestion::createWithOptions($result->name()->head(), [
                'short_description' => $result->name()->__toString(),
                'type' => $this->suggestionType($result),
                'class_import' => $this->classImport($result),
                'name_import' => $result->name()->__toString(),
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

    protected function classImport(NameSearchResult $result): ?string
    {
        if ($result->type()->isClass()) {
            return $result->name()->__toString();
        }

        return null;
    }
}
