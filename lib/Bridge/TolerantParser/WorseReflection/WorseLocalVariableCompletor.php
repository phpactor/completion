<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Microsoft\PhpParser\Node;
use Phpactor\Completion\Adapter\WorseReflection\Completor\LocalVariable\VariableWithNode;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Response;
use Phpactor\WorseReflection\Reflector;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\WorseReflection\Core\Inference\Variable;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Microsoft\PhpParser\Node\Expression\Variable as TolerantVariable;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\Expression\AssignmentExpression;

class WorseLocalVariableCompletor implements TolerantCompletor
{
    const NAME_REGEX = '{[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]}';
    const VALID_PRECHARS = [' ', '=', '[', '('];
    const INVALID_PRECHARS = [ ':' ];

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var ObjectFormatter
     */
    private $informationFormatter;

    public function __construct(Reflector $reflector, ObjectFormatter $typeFormatter = null)
    {
        $this->reflector = $reflector;
        $this->informationFormatter = $typeFormatter ?: new ObjectFormatter();
    }

    public function complete(Node $node, string $source, int $offset): Response
    {
        if (false === $this->couldComplete($node, $source, $offset)) {
            return Response::new();
        }

        $partialSource = mb_substr($source, 0, $offset);

        $dollarPosition = strrpos($partialSource, '$');
        if (false === $dollarPosition) {
            return Response::new();
        }

        $partialMatch = mb_substr($partialSource, $dollarPosition);
        $suggestions = Suggestions::new();

        $offset = $this->offsetToReflect($node, $offset);
        $reflectionOffset = $this->reflector->reflectOffset($source, $offset);
        $frame = $reflectionOffset->frame();

        // Get all declared variables up until the offset. The most
        // recently declared variables should be first (which is why
        // we reverse the array).
        $reversedLocals = $this->orderedVariablesUntilOffset($frame, $offset);

        // Ignore variables that have already been suggested.
        $seen = [];

        /** @var Variable $local */
        foreach ($reversedLocals as $local) {

            if (isset($seen[$local->name()])) {
                continue;
            }

            $name = ltrim($partialMatch, '$');
            $matchPos = -1;

            if ($name) {
                $matchPos = mb_strpos($local->name(), $name);
            }

            if ('$' !== $partialMatch && 0 !== $matchPos) {
                continue;
            }

            $seen[$local->name()] = true;

            $suggestions->add(
                Suggestion::create(
                    'v',
                    $local->name(),
                    $this->informationFormatter->format($local)
                )
            );
        }

        return Response::fromSuggestions($suggestions);
    }

    private function orderedVariablesUntilOffset(Frame $frame, int $offset)
    {
        return array_reverse(iterator_to_array($frame->locals()->lessThanOrEqualTo($offset)));
    }

    private function offsetToReflect(Node $node, int $offset)
    {
        $parentNode = $node->parent;
        
        // If the parent is an assignment expression, then only parse
        // until the start of the expression, not the start of the variable
        // under completion:
        //
        //     $left = $lef<>
        //
        // Otherwise $left will be evaluated to <unknown>.
        if ($parentNode instanceof AssignmentExpression) {
            $offset = $parentNode->getFullStart();
        }
        return $offset;
    }

    private function couldComplete(Node $node = null, string $source, int $offset): bool
    {
        if (null === $node) {
            return false;
        }

        $parentNode = $node->parent;

        if ($parentNode instanceof MemberAccessExpression) {
            return false;
        }

        if ($parentNode instanceof ScopedPropertyAccessExpression) {
            return false;
        }

        if ($node instanceof TolerantVariable) {
            return true;
        }

        return false;
    }
}
