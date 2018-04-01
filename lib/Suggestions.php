<?php

namespace Phpactor\Completion;

use IteratorAggregate;
use ArrayIterator;

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
                'info' => $suggestion->info()
            ];
        }, $this->suggestions);
    }

    public static function new(): Suggestions
    {
        return new self([]);
    }

    public function fromSuggestions(array $suggestions)
    {
        return new self($suggestions);
    }
}
