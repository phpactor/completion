<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\WorseReflection\Core\Name;

class WorseConstantCompletor implements TolerantCompletor
{
    public function complete(Node $node, string $source, int $offset): Response
    {
        if (!$node instanceof QualifiedName) {
            return Response::new();
        }

        $definedConstants = get_defined_constants();
        $partial = $node->getText();

        $suggestions = Suggestions::new();
        foreach ($definedConstants as $name => $value) {
            $name = Name::fromString((string) $name);

            if (0 === mb_strpos($name->short(), $partial)) {
                $suggestions->add(Suggestion::createWithOptions(
                    $name->short(),
                    [
                        'type' => Suggestion::TYPE_CONSTANT,
                        'short_description' => sprintf('%s = %s', $name->full(), var_export($value, true))
                    ]
                ));
            }
        }

        return Response::fromSuggestions($suggestions);
    }
}
