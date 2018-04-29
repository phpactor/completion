<?php

namespace Phpactor\Completion\Bridge\WorseReflection\Formatter;

use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunction;

class FunctionFormatter implements Formatter
{
    public function canFormat($object): bool
    {
        return $object instanceof ReflectionFunction;
    }

    public function format(ObjectFormatter $formatter, $function): string
    {
        assert($function instanceof ReflectionFunction);

        $info = [
            $function->name()
        ];

        $paramInfos = [];

        /** @var ReflectionParameter $parameter */
        foreach ($function->parameters() as $parameter) {
            $paramInfos[] = $formatter->format($parameter);
        }
        $info[] = '(' . implode(', ', $paramInfos) . ')';

        $returnTypes = $function->inferredTypes();

        if ($returnTypes->count() > 0) {
            $info[] = ': ' . $formatter->format($returnTypes);
        }

        return implode('', $info);
    }
}
