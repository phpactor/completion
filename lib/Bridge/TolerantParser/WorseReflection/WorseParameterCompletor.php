<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use LogicException;
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
use Phpactor\WorseReflection\Core\Exception\CouldNotResolveNode;
use Phpactor\WorseReflection\Core\Inference\Variable as WorseVariable;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
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
        $response = Response::new();
        if (!$node instanceof Variable) {
            return $response;
        }

        $callExpression = $node->getFirstAncestor(CallExpression::class);
        if (!$callExpression) {
            return $response;
        }

        assert($callExpression instanceof CallExpression);
        $callableExpression = $callExpression->callableExpression;

        if (!$callableExpression instanceof MemberAccessExpression) {
            return $response;
        }

        $variables = $this->variableCompletions($node, $source, $offset);

        $suggestions = [];
        $call = $this->reflector->reflectMethodCall($source, $callableExpression->getEndPosition());

        try {
            $method = $call->class()->methods()->get($call->name());
        } catch (CouldNotResolveNode $exception) {
            $response->issues()->add($exception->getMessage());
            return $response;
        }

        if ($method->parameters()->count() === 0) {
            return Response::new();
        }

        $paramIndex = $this->paramIndex($callableExpression);

        if ($this->numberOfArgumentsExceedParameterArity($method, $paramIndex)) {
            $response->issues()->add('Parameter index exceeds parameter arity');
            return $response;
        }

        $parameter = $this->reflectedParameter($method, $paramIndex);

        foreach ($variables as $variable) {
            if (
                $variable->symbolContext()->types()->count() && 
                false === $this->isVariableValidForParameter($variable, $parameter)
            ) {
                // parameter has no types and is not valid for this position, ignore it
                continue;
            }

            $suggestions[] = Suggestion::create(
                'v',
                '$' . $variable->name(),
                sprintf(
                    '%s => param #%d %s',
                    $this->formatter->format($variable->symbolContext()->types()),
                    $paramIndex,
                    $this->formatter->format($parameter)
                )
            );
        }

        return Response::fromSuggestions(Suggestions::fromSuggestions($suggestions));
    }

    private function paramIndex(MemberAccessExpression $exp)
    {
        assert(null !== $exp->parent);
        $node = $exp->parent->getFirstDescendantNode(ArgumentExpressionList::class);
        assert($node instanceof ArgumentExpressionList);

        $index = 0;
        /** @var ArgumentExpression $element */
        foreach ($node->getElements() as $element) {
            $index++;
            if (!$element->expression instanceof Variable) {
                continue;
            }

            $name = $element->expression->getName();

            if ($name instanceof MissingToken) {
                continue;
            }
        }

        return $index;
    }

    private function isVariableValidForParameter(WorseVariable $variable, ReflectionParameter $parameter)
    {
        if ($parameter->inferredTypes()->best() == Type::undefined()) {
            return true;
        }

        $valid = false;

        /** @var Type $variableType */
        foreach ($variable->symbolContext()->types() as $variableType) {

            if ($variableType->isClass() ) {
                $variableTypeClass = $this->reflector->reflectClassLike($variableType->className());
            }

            foreach ($parameter->inferredTypes() as $parameterType) {
                if ($variableType->isClass() && $parameterType->isClass() && $variableTypeClass->isInstanceOf($parameterType->className())) {
                    return true;
                    
                }

                if ($variableType == $parameterType) {
                    return true;
                }
            }
        }
        return false;
    }

    private function reflectedParameter(ReflectionMethod $method, $paramIndex)
    {
        $reflectedIndex = 1;
        /** @var ReflectionParameter $parameter */
        foreach ($method->parameters() as $parameter) {
            if ($reflectedIndex == $paramIndex) {
                return $parameter;
                break;
            }
            $reflectedIndex++;
        }

        throw new LogicException(sprintf('Could not find parameter for index "%s"', $paramIndex));
    }

    private function numberOfArgumentsExceedParameterArity(ReflectionMethod $method, $paramIndex)
    {
        return $method->parameters()->count() < $paramIndex;
    }
}
