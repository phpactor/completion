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

class WorseLocalVariableCompletor extends AbstractVariableCompletor implements TolerantCompletor
{
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
        parent::__construct($reflector);
        $this->reflector = $reflector;
        $this->informationFormatter = $typeFormatter ?: new ObjectFormatter();
    }

    public function complete(Node $node, string $source, int $offset): Response
    {
        if (false === $this->couldComplete($node, $source, $offset)) {
            return Response::new();
        }

        $suggestions = Suggestions::new();
        foreach ($this->variableCompletions($node, $source, $offset) as $local) {
            $suggestions->add(
                Suggestion::createWithOptions(
                    '$' . $local->name(),
                    [
                        'type' => Suggestion::TYPE_VARIABLE,
                        'short_description' => $this->informationFormatter->format($local)
                    ]
                )
            );
        }

        return Response::fromSuggestions($suggestions);
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
