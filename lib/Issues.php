<?php

namespace Phpactor\Completion;

use ArrayIterator;
use IteratorAggregate;

class Issues implements IteratorAggregate
{
    /**
     * @var array
     */
    private $issues;

    /**
     * @var string[]
     */
    public function __construct(array $issues)
    {
        $this->issues = $issues;
    }

    public static function fromStrings(array $issues)
    {
        return new self($issues);
    }

    public function add(string $issue)
    {
        $this->issues[] = $issue;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->issues);
    }

    public function toArray(): array
    {
        return $this->issues;
    }

    public function new(): Issues
    {
        return new self([]);
    }
}
