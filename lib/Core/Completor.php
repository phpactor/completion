<?php

namespace Phpactor\Completion\Core;

use Generator;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

interface Completor
{
    /**
     * @return Generator<Suggestion>
     */
    public function complete(TextDocument $source, ByteOffset $byteOffset): Generator;
}
