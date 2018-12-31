<?php

namespace Phpactor\Completion\Core;

use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

interface HoverTextProvider
{
    public function hover(TextDocument $source, ByteOffset $byteOffset): HoverText;
}
