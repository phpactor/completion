<?php

namespace Phpactor\Completion\Core;

use IteratorAggregate;
use ArrayIterator;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;

class Suggestions implements IteratorAggregate
{
    /**
     * @var Suggestion[]
     */
    private $suggestions;

    /**
     * @param Suggestion[] $suggestions
     */
    public function __construct(array $suggestions = [])
    {
        $this->suggestions = $suggestions;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->suggestions);
    }

    public function add(Suggestion $suggestion)
    {
        $this->suggestions[] = $suggestion;
    }

    public function toArray()
    {
        return array_map(function (Suggestion $suggestion) {
            return [
                'type' => $suggestion->type(),
                'name' => $suggestion->name(),
                'info' => $suggestion->info(),
                'class_import' => $suggestion->classImport(),
            ];
        }, $this->suggestions);
    }

    public static function new(): Suggestions
    {
        return new self([]);
    }

    public static function fromSuggestions(array $suggestions): Suggestions
    {
        return new self($suggestions);
    }

    public function sorted(): Suggestions
    {
        $suggestions = $this->suggestions;
        usort($suggestions, function (Suggestion $a, Suggestion $b) {
            return $a->name() <=> $b->name();
        });

        return new self($suggestions);
    }

    public function startingWith(string $partialMatch): Suggestions
    {
        if (empty($partialMatch)) {
            return $this;
        }
        return new self(array_values(array_filter($this->suggestions, function (Suggestion $suggestion) use ($partialMatch) {
            return 0 === strpos($suggestion->name(), $partialMatch);
        })));
    }
}
