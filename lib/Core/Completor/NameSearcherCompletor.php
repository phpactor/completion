<?php

namespace Phpactor\Completion\Core\Completor;

use Generator;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;

abstract class NameSearcherCompletor
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
     * @return Generator<Suggestion>
     */
    protected function completeName(string $name): Generator
    {
        foreach ($this->nameSearcher->search($name) as $result) {
            yield $this->createSuggestion(
                $result,
                $this->createSuggestionOptions($result),
            );
        }

        return true;
    }

    protected function createSuggestion(NameSearchResult $result, array $options = []): Suggestion
    {
        $options = array_merge($this->createSuggestionOptions($result), $options);

        return Suggestion::createWithOptions($result->name()->head(), $options);
    }

    protected function createSuggestionOptions(NameSearchResult $result): array
    {
        return [
            'short_description' => $result->name()->__toString(),
            'type' => $this->suggestionType($result),
            'class_import' => $this->classImport($result),
            'name_import' => $result->name()->__toString(),
            'priority' => Suggestion::PRIORITY_LOW,
        ];
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
