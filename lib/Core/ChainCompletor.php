<?php

namespace Phpactor\Completion\Core;

use Generator;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

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

    public function complete(TextDocument $source, ByteOffset $offset): Generator
    {
        foreach ($this->completors as $completor) {
            foreach ($completor->complete($source, $offset) as $suggestion) {
                yield $suggestion;
            }
        }
    }
}
