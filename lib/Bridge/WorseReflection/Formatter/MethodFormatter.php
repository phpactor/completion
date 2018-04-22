<?php

namespace Phpactor\Completion\Bridge\WorseReflection\Formatter;

use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionMethod;
use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;

class MethodFormatter implements Formatter
{
    public function canFormat($object): bool
    {
        return $object instanceof ReflectionMethod;
    }

    public function format(ObjectFormatter $formatter, $method): string
    {
        assert($method instanceof ReflectionMethod);

        $info = [
            substr((string) $method->visibility(), 0, 3),
            ' ',
            $method->name()
        ];

        if ($method->isAbstract()) {
            array_unshift($info, 'abstract ');
        }

        $paramInfos = [];

        /** @var ReflectionParameter $parameter */
        foreach ($method->parameters() as $parameter) {
            $paramInfo = [];
            if ($parameter->inferredTypes()->count()) {
                $paramInfo[] = $formatter->format($parameter->inferredTypes());
            }
            $paramInfo[] = '$' . $parameter->name();

            if ($parameter->default()->isDefined()) {
                $paramInfo[] = '= '. str_replace(PHP_EOL, '', var_export($parameter->default()->value(), true));
            }
            $paramInfos[] = implode(' ', $paramInfo);
        }
        $info[] = '(' . implode(', ', $paramInfos) . ')';

        $returnTypes = $method->inferredTypes();

        if ($returnTypes->count() > 0) {
            $info[] = ': ' . $formatter->format($returnTypes);
        }

        return implode('', $info);
    }

}
