<?php

namespace Phpactor\Completion\Adapter\WorseReflection\Formatter;

use Microsoft\PhpParser\Node\DelimitedList\ArgumentExpressionList;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable as ParserVariable;
use Phpactor\Completion\Adapter\WorseReflection\Completor\LocalVariable\VariableWithNode;
use Phpactor\WorseReflection\Core\Exception\SourceNotFound;
use Phpactor\WorseReflection\Core\Inference\Variable;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;
use Phpactor\WorseReflection\Reflector;

class VariableWithNodeFormatter implements Formatter
{
    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function canFormat($object): bool
    {
        return $object instanceof VariableWithNode;
    }

    public function format(ObjectFormatter $formatter, $object): string
    {
        assert($object instanceof VariableWithNode);

        if ($callExpression = $object->node()->getFirstAncestor(CallExpression::class)) {
            if ($formatted = $this->formatArgumentExpression($formatter, $object->variable(), $callExpression, $object->node())) {
                return $formatted;
            }
        }

        return $formatter->format($object->variable()->symbolContext()->types());
    }

    private function formatArgumentExpression(ObjectFormatter $formatter, Variable $variable, CallExpression $node, ParserVariable $variableNode)
    {
        $expression = $node->callableExpression;

        if (!$expression instanceof MemberAccessExpression) {
            return;
        }

        $offset = $this->reflector->reflectOffset($node->getFileContents(), $node->getStart());
        $className = $offset->symbolContext()->type();

        try {
            $reflectionClass = $this->reflector->reflectClass((string) $className);
        } catch (SourceNotFound $notFound) {
            return;
        }

        $memberName = $expression->memberName->getText($node->getFileContents());

        if (!$reflectionClass->methods()->has($memberName)) {
            return;
        }

        $method = $reflectionClass->methods()->get($memberName);
        foreach ($variableNode->parent->getChildNodes() as $nodeIndex => $child) {
            if ($child === $variableNode) {
                break;
            }
        }

        $params = [];
        $reflectionIndex = 0;
        foreach ($method->parameters() as $parameter) {
            if ($nodeIndex === $reflectionIndex) {
                $params[] = '>>' . $formatter->format($parameter) . '<<';
            }

            $params[] = $formatter->format($parameter);
            $reflectionIndex++;
        }

        return sprintf(
            '%s#(%s)',
            $formatter->format($variable->symbolContext()->types()),
            implode(', ', $params)
        );
    }
}
