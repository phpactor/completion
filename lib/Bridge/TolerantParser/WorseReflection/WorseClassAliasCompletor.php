<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Microsoft\PhpParser\Node;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\WorseReflection\Reflector;

class WorseClassAliasCompletor implements TolerantCompletor
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
        $this->reflector = $reflector;
        $this->formatter = $formatter;
    }

    public function complete(Node $node, string $source, int $offset): Response
    {
        $namespaceImports = $node->getImportTablesForCurrentScope()[0];
        $suggestions = [];

        foreach ($namespaceImports as $alias => $resolvedName) {
            if ($alias === (string) $resolvedName) {
                continue;
            }

            $suggestions[] = Suggestion::createWithOptions(
                $alias,
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'short_description' => sprintf('Alias for: %s', (string) $resolvedName)
                ]
            );
        }
        return Response::fromSuggestions(Suggestions::fromSuggestions($suggestions));
    }
}
