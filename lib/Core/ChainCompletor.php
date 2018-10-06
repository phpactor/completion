<?php

namespace Phpactor\Completion\Core;

use Generator;

class ChainCompletor implements Completor
{
    /**
     * @var Completor[]
     */
    private $completors;

    /**
     * @param Completor[] $completors
     */
    public function __construct(array $completors)
    {
        $this->completors = $completors;
    }

    public function complete(string $source, int $offset): Generator
    {
        foreach ($this->completors as $completor) {
            foreach ($completor->complete($source, $offset) as $suggestion) {
                yield $suggestion;
            }
        }
    }
}
