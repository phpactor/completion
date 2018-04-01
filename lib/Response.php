<?php

namespace Phpactor\Completion;

use IteratorAggregate;

class Response implements IteratorAggregate
{
    /**
     * @var Suggestions
     */
    private $suggestions;

    /**
     * @var Issues
     */
    private $issues;

    public function __construct(Suggestions $suggestions, Issues $issues)
    {
        $this->suggestions = $suggestions;
        $this->issues = $issues;
    }

    public function suggestions(): Suggestions
    {
        return $this->suggestions;
    }

    public function issues(): Issues
    {
        return $this->issues;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return $this->suggestions;
    }

    public static function new(): Response
    {
        return new self(Suggestions::new(), Issues::new());
    }

    public function fromSuggestions(Suggestions $suggestions)
    {
        return new self($suggestions, Issues::new());
    }

    public function merge(Response $response): Response
    {
        foreach ($response->suggestions() as $suggestion) {
            $this->suggestions->add($suggestion);
        }

        foreach ($response->issues() as $issue) {
            $this->issues->add($issue);
        }

        return $this;
    }
}
