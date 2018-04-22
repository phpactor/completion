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
use Phpactor\WorseReflection\Reflector;

class WorseParameterCompletor implements TolerantCompletor
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var WorseLocalVariableCompletor
     */
    private $localVariableCompletor;

    /**
     * @var ObjectFormatter
     */
    private $formatter;

    public function __construct(Reflector $reflector, ObjectFormatter $formatter, WorseLocalVariableCompletor $localVariableCompletor = null)
    {
        $this->reflector = $reflector;
        $this->localVariableCompletor = $localVariableCompletor ?: new WorseLocalVariableCompletor($reflector, $formatter);
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

        $variableSuggestions = $this->localVariableCompletor->complete($node, $source, $offset)->suggestions();

        $suggestions = [];
        $call = $this->reflector->reflectMethodCall($source, $callableExpression->getEndPosition());

        $paramIndex = $this->paramIndex($callableExpression);
        foreach ($variableSuggestions as $variableSuggestion) {
            $method = $call->class()->methods()->get($call->name());

            // TODO: Add issue?
            if ($method->parameters()->count() === 0) {
                return Response::new();
            }

            $reflectedIndex = 0;
            // TODO: Add atIndex method
            foreach ($method->parameters() as $parameter) {
                if ($reflectedIndex === $paramIndex) {
                    break;
                }
            }

            $suggestions[] = Suggestion::create(
                'v',
                $variableSuggestion->name(),
                sprintf(
                    '%s to parameter %s',
                    $variableSuggestion->info(),
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
}
