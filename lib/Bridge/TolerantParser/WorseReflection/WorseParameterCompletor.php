<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\DelimitedList\ArgumentExpressionList;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\WorseReflection\Core\Inference\Variable as WorseVariable;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Reflector;

class WorseParameterCompletor extends AbstractVariableCompletor implements TolerantCompletor
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var ObjectFormatter
     */
    private $formatter;

    public function __construct(Reflector $reflector, ObjectFormatter $formatter)
    {
        parent::__construct($reflector);
        $this->reflector = $reflector;
        $this->formatter = $formatter;
    }

    public function complete(Node $node, string $source, int $offset): Response
    {
        if (!$node instanceof Variable) {
            return Response::new();
        }

        $callExpression = $node->getFirstAncestor(CallExpression::class);
        if (!$callExpression) {
            return Response::new();
        }

        assert($callExpression instanceof CallExpression);
        $callableExpression = $callExpression->callableExpression;

        if (!$callableExpression instanceof MemberAccessExpression) {
            return Response::new();
        }

        $variables = $this->variableCompletions($node, $source, $offset);

        $suggestions = [];
        $call = $this->reflector->reflectMethodCall($source, $callableExpression->getEndPosition());

        $paramIndex = $this->paramIndex($callableExpression);
        foreach ($variables as $variable) {
            $method = $call->class()->methods()->get($call->name());

            if ($method->parameters()->count() === 0) {
                return Response::new();
            }

            $reflectedIndex = 0;

            /** @var ReflectionParameter $parameter */
            foreach ($method->parameters() as $parameter) {
                if ($reflectedIndex === $paramIndex) {
                    break;
                }
            }

            $valid = $this->isVariableValidForParameter($variable, $parameter);

            // variable is not typed or is not a valid type
            if ($variable->symbolContext()->types()->count() && false === $valid) {
                continue;
            }

            $suggestions[] = Suggestion::create(
                'v',
                $variable->name(),
                sprintf(
                    '%s => param #%d %s',
                    $this->formatter->format($variable->symbolContext()->types()),
                    $paramIndex - 1,
                    $this->formatter->format($parameter)
                )
            );
        }

        return Response::fromSuggestions(Suggestions::fromSuggestions($suggestions));
    }

    private function paramIndex(MemberAccessExpression $exp)
    {
        $node = $exp->parent->getFirstDescendantNode(ArgumentExpressionList::class);
        assert($node instanceof ArgumentExpressionList);

        $index = 0;
        /** @var ArgumentExpression $element */
        foreach ($node->getElements() as $element) {
            if (!$element->expression instanceof Variable) {
                continue;
            }

            $name = $element->expression->getName();

            if ($name instanceof MissingToken) {
                continue;
            }

            $index++;
        }

        return $index;
    }

    private function isVariableValidForParameter(WorseVariable $variable, ReflectionParameter $parameter)
    {
        if ($parameter->inferredTypes()->best() == Type::undefined()) {
            return true;
        }

        $valid = false;
        foreach ($variable->symbolContext()->types() as $variableType) {
            foreach ($parameter->inferredTypes() as $parameterType) {
                if ($variableType == $parameterType) {
                    $valid = true;
                }
            }
        }
        return $valid;
    }
}
