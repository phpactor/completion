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
}
