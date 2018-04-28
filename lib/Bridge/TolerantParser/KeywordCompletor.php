<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\ClassBaseClause;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;

class KeywordCompletor implements TolerantCompletor
{
    public function complete(Node $node, string $source, int $offset): Response
    {
        if (false === $this->couldComplete($node, $offset)) {
            return Response::new();
        }

        $suggestions = Suggestions::new();
        $suggestions->add(Suggestion::create('k', 'extends ', ''));
        $suggestions->add(Suggestion::create('k', 'implements ', ''));


        return Response::fromSuggestions($suggestions);
    }

    private function couldComplete(Node $node, int $offset): bool
    {
        if ($node instanceof ClassDeclaration && $offset > $node->getEndPosition()) {
            return true;
        }

        return false;
    }
}
