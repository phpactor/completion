<?php

namespace Phpactor\Completion\Adapter\WorseReflection\Formatter;

use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;

class ParameterFormatter implements Formatter
{
    public function canFormat($object): bool
    {
        return $object instanceof ReflectionParameter;
    }

    public function format(ObjectFormatter $formatter, $object): string
    {
        assert($object instanceof ReflectionParameter);

        $paramInfo = [];

        if ($object->inferredTypes()->count()) {
            $paramInfo[] = $formatter->format($object->inferredTypes());
        }
        $paramInfo[] = '$' . $object->name();

        if ($object->default()->isDefined()) {
            $paramInfo[] = '= '. str_replace(PHP_EOL, '', var_export($object->default()->value(), true));
        }
        return implode(' ', $paramInfo);
    }

}
