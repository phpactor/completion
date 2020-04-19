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
    public function canFormat(object $functionLike): bool
    {
        return $functionLike instanceof ReflectionFunction
            || $functionLike instanceof ReflectionMethod;
    }

    public function format(ObjectFormatter $formatter, object $functionLike): string
    {
        assert(
            $functionLike instanceof ReflectionFunction
            || $functionLike instanceof ReflectionMethod
        );

        $name = $functionLike instanceof ReflectionFunction
            ? $functionLike->name()->short()
            : $functionLike->name()
        ;
        $parameters = $functionLike->parameters();

        if (0 === $parameters->count()) {
            return \sprintf('%s()', $name);
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
