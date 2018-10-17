<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Generator;
use Microsoft\PhpParser\Node;

class LimitingCompletor implements TolerantCompletor
{
    /**
     * @var TolerantCompletor
     */
    private $completor;

    /**
     * @var int
     */
    private $limit;

    public function __construct(TolerantCompletor $completor, int $limit = 50)
    {
        $this->completor = $completor;
        $this->limit = $limit;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(Node $node, string $source, int $offset): Generator
    {
        $count = 0;
        foreach ($this->completor->complete($node, $source, $offset) as $suggestion) {
            yield $suggestion;

            if (++$count === $this->limit) {
                break;
            }
        }
    }
}
