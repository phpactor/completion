<?php

namespace Phpactor\Completion\Bridge\WorseReflection\Formatter;

use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;

class InterfaceFormatter implements Formatter
{
    public function canFormat($object): bool
    {
        return $object instanceof ReflectionInterface;
    }

    public function format(ObjectFormatter $formatter, $class): string
    {
        assert($class instanceof ReflectionInterface);

        return $class->name();
    }
}
