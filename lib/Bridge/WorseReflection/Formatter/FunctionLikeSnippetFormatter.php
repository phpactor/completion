<?php

namespace Phpactor\Completion\Bridge\WorseReflection\Formatter;

use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\Util\Snippet\Placeholder;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunction;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;

class FunctionLikeSnippetFormatter implements Formatter
{
    public function canFormat(object $function): bool
    {
        return $function instanceof ReflectionFunction
            || $function instanceof ReflectionMethod;
    }

    public function format(ObjectFormatter $formatter, object $function): string
    {
        assert(
            $function instanceof ReflectionFunction
            || $function instanceof ReflectionMethod
        );

        $name = $function instanceof ReflectionFunction
            ? $function->name()->short()
            : $function->name()
        ;
        $parameters = $function->parameters();

        if (0 === $parameters->count()) {
            return "$name()";
        }

        $placeholders = [];
        $position = 0;
        /** @var ReflectionParameter $parameter */
        foreach ($parameters as $parameter) {
            if ($parameter->default()->isDefined()) {
                continue; // Ignore optional parameters
            }

            $placeholders[] = Placeholder::escape(++$position, '$'.$parameter->name());
        }

        return \sprintf(
            '%s(%s)%s',
            $name,
            // If no placeholders then all parameters are optional
            // But we still want to stop between the parentheses
            \implode(', ', $placeholders ?: [Placeholder::raw(1)]),
            Placeholder::raw(0)
        );
    }
}
