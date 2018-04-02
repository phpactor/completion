<?php

namespace Phpactor\Completion\Core;

use ArrayIterator;
use IteratorAggregate;
use Phpactor\Completion\Core\Issues;

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

    public static function new(): Issues
    {
        return new self([]);
    }
}
