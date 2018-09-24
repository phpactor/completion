<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\ResolvedName;
use Phpactor\Completion\Bridge\TolerantParser\Qualifier\ClassQualifier;
use Phpactor\Completion\Bridge\TolerantParser\Qualifier\Qualifier;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantQualifiable;
use Phpactor\Completion\Bridge\TolerantParser\TolerantQualifier;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\WorseReflection\Reflector;

class WorseClassAliasCompletor implements TolerantCompletor, TolerantQualifiable
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var ClassQualifier
     */
    private $qualifier;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function complete(Node $node, string $source, int $offset): Response
    {
        $namespaceImports = $node->getImportTablesForCurrentScope()[0];
        $suggestions = [];

        /** @var ResolvedName $resolvedName */
        foreach ($namespaceImports as $alias => $resolvedName) {
            $parts = $resolvedName->getNameParts();
            if (empty($parts)) {
                continue;
            }

            $lastPart = array_pop($parts);

            if ($alias === $lastPart) {
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

    public function qualifier(): TolerantQualifier
    {
        return new ClassQualifier();
    }
}
