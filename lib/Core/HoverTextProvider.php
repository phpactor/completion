<?php

namespace Phpactor\Completion\Core;

interface HoverTextProvider
{
    public function hover(string $source, int $byteOffset): HoverText;
}
