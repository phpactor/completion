<?php

namespace Phpactor\Completion\Core\Completor;

use Generator;
use Phpactor\Completion\Core\Completor;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

class DedupeCompletor implements Completor
{
    /**
     * @var Completor
     */
    private $innerCompletor;

    public function __construct(Completor $innerCompletor)
    {
        $this->innerCompletor = $innerCompletor;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(TextDocument $source, ByteOffset $byteOffset): Generator
    {
        $seen = [];
        foreach ($this->innerCompletor->complete($source, $byteOffset) as $suggestion) {
            if (isset($seen[$suggestion->name()])) {
                continue;
            }
            $seen[$suggestion->name()] = $suggestion;
            yield $suggestion;
        }
    }
}
