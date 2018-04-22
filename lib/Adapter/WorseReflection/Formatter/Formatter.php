<?php

namespace Phpactor\Completion\Adapter\WorseReflection\Formatter;

use Microsoft\PhpParser\Node;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Core\Inference\Variable;

interface Formatter
{
    public function canFormat($object): bool;

    public function format(ObjectFormatter $formatter, $object): string;
}
