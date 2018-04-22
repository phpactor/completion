<?php

namespace Phpactor\Completion\Bridge\WorseReflection\Formatter;

use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;

class TypeFormatter implements Formatter
{
    public function canFormat($object): bool
    {
        return $object instanceof Type;
    }

    public function format(ObjectFormatter $formatter, $object): string
    {
        assert($object instanceof Type);

        if (false === $object->isDefined()) {
            return '<unknown>';
        }

        $shortName = $object->short();

        if ($object->arrayType()->isDefined()) {
            // generic
            if ($object->isClass()) {
                return sprintf('%s<%s>', $shortName, $object->arrayType()->short());
            }

            // array
            return sprintf('%s[]', $object->arrayType()->short());
        }

        return $shortName;
    }
}
