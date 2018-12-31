<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Phpactor\Completion\Core\HoverText;
use Phpactor\Completion\Core\HoverTextProvider;

class WorseHoverTextProvider implements HoverTextProvider
{
    public function hover(string $source, int $byteOffset): HoverText
    {
    }
}
