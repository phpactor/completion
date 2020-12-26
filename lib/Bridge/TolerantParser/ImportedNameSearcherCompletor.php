<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\ResolvedName;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Util\OffsetHelper;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

/**
 * Replace the suggestions from the decorated decorator by new ones based on the impor table.
 */
final class ImportedNameSearcherCompletor implements NameSearcherCompletor
{
    /**
     * @var NameSearcherCompletor
     */
    private $decorated;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(NameSearcherCompletor $decorated, Parser $parser = null)
    {
        $this->decorated = $decorated;
        $this->parser = $parser ?: new Parser();
    }

    /**
     * {@inheritDoc}
     */
    public function complete(TextDocument $source, ByteOffset $byteOffset, string $name = null): Generator
    {
        $importTable = $this->getClassImportTableAtPosition($source, $byteOffset);
        $suggestions = $this->decorated->complete($source, $byteOffset, $name);

        foreach ($suggestions as $suggestion) {
            $resolvedSuggestions = $this->resolveAliasSuggestions($importTable, $suggestion);

            // Trick to avoid any BC break when converting to an array
            // https://www.php.net/manual/fr/language.generators.syntax.php#control-structures.yield.from
            foreach ($resolvedSuggestions as $resolvedSuggestion) {
                yield $resolvedSuggestion;
            }
        }

        return $suggestions->getReturn();
    }

    private function truncateSource(string $source, int $byteOffset): string
    {
        // truncate source at byte offset - we don't want the rest of the source
        // file contaminating the completion (for example `$foo($<>\n    $bar =
        // ` will evaluate the Variable node as an expression node with a
        // double variable `$\n    $bar = `
        $truncatedSource = substr($source, 0, $byteOffset);

        // determine the last non-whitespace _character_ offset
        $characterOffset = OffsetHelper::lastNonWhitespaceCharacterOffset($truncatedSource);

        // truncate the source at the character offset
        $truncatedSource = mb_substr($source, 0, $characterOffset);

        return $truncatedSource;
    }

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

        $suggestionFqcn = $suggestion->nameImport();
        $originalName = $suggestion->name();
        $originalSnippet = $suggestion->snippet();
        $suggestions = [];

        foreach ($importTable as $alias => $resolvedName) {
            $importFqcn = $resolvedName->getFullyQualifiedNameText();

            if ($suggestionFqcn === $importFqcn && $originalName === $alias) {
                // Ignore the original suggestion, another completor already retruns it
                continue;
            }

            if (0 !== strpos($suggestionFqcn, $importFqcn)) {
                // Ignore imported name that are not part of the one from suggestion
                continue;
            }

            $name = $alias.substr($suggestionFqcn, strlen($importFqcn));
            $suggestions[$alias] = $suggestion->withoutNameImport()->withName($name);

            if ($originalSnippet && $originalName !== $name) {
                $snippet = str_replace($originalName, $name, $originalSnippet);
                $suggestions[$alias] = $suggestions[$alias]->withSnippet($snippet);
            }
        }

        return array_values($suggestions);
    }

    /**
     * @return ResolvedName[]
     */
    private function getClassImportTableAtPosition(TextDocument $source, ByteOffset $byteOffset): array
    {
        // We only need the closest node to retrieve the import table
        // It's not a big deal if it's not the completed node as long as it has
        // the same import table
        $node = $this->getClosestNodeAtPosition(
            $this->parser->parseSourceFile((string) $source),
            $byteOffset->toInt(),
        );

        try {
            [$importTable] = $node->getImportTablesForCurrentScope();
        } catch (\Exception $e) {
            // If the node does not have an import table (SourceFileNode for example)
            $importTable = [];
        }

        return $importTable;
    }

    private function getClosestNodeAtPosition(SourceFileNode $sourceFileNode, int $position): Node
    {
        $lastNode = $sourceFileNode;
        /** @var Node $node */
        foreach ($sourceFileNode->getDescendantNodes() as $node) {
            if ($position < $node->getFullStart()) {
                return $lastNode;
            }

            $lastNode = $node;
        }

        return $lastNode;
    }
}
