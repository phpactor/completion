<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\ResolvedName;
use Phpactor\Completion\Core\Suggestion;

trait ResolveAliasSuggestionsTrait
{
    /**
     * Add suggestions when a class is already imported with an alias or when a relative name is abailable.
     *
     * Will update the suggestion to remove the import_name option if already imported.
     * Will add a suggestion if the class is imported under an alias.
     * Will add a suggestion if part of the namespace is imported (i.e. ORM\Column is a relative name).
     *
     * @param ResolvedName[] $importTable
     *
     * @return Suggestion[]
     */
    private function resolveAliasSuggestions(array $importTable, Suggestion $suggestion): array
    {
        if (Suggestion::TYPE_CLASS !== $suggestion->type()) {
            return [$suggestion];
        }

        $suggestionFqcn = $suggestion->classImport();
        $suggestions = [$suggestion->name() => $suggestion];
        foreach ($importTable as $alias => $resolvedName) {
            $importFqcn = $resolvedName->getFullyQualifiedNameText();

            if (0 !== strpos($suggestionFqcn, $importFqcn)) {
                continue;
            }

            $name = $alias.substr($suggestionFqcn, strlen($importFqcn));

            $suggestions[$alias] = $suggestion->withoutNameImport()->withName($name);
        }

        return array_values($suggestions);
    }

    /**
     * @return ResolvedName[]
     */
    private function getClassImportTablesForNode(Node $node): array
    {
        try {
            [$importTable] = $node->getImportTablesForCurrentScope();
        } catch (\Exception $e) {
            $importTable = [];
        }

        return $importTable;
    }
}
