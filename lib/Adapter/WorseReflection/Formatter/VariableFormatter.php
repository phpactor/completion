<?php

namespace Phpactor\Completion\Adapter\WorseReflection\Formatter;

use Phpactor\WorseReflection\Core\Inference\Variable;

class VariableFormatter implements Formatter
{
    public function canFormat($object): bool
    {
        return $object instanceof Variable;
    }

    public function format(ObjectFormatter $formatter, $object): string
    {
        assert($object instanceof Variable);

        return $formatter->format($object->symbolContext()->types());
    }
}
