<?php

namespace Phpactor\Completion\Core\Completor;

use Generator;
use Phpactor\Completion\Core\Completor;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

interface NameSearcherCompletor extends Completor
{
    /**
     * {@inheritDoc}
     */
    public function complete(
        TextDocument $source,
        ByteOffset $byteOffset,
        string $name = null
    ): Generator;
}
