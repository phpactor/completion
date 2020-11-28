<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\ResolvedName;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Util\OffsetHelper;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

class ChainTolerantCompletor implements Completor
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var TolerantCompletor[]
     */
    private $tolerantCompletors = [];

    /**
     * @param TolerantCompletor[] $tolerantCompletors
     */
    public function __construct(array $tolerantCompletors, Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();
        $this->tolerantCompletors = $tolerantCompletors;
    }

    public function complete(TextDocument $source, ByteOffset $byteOffset): Generator
    {
        $truncatedSource = $this->truncateSource((string) $source, $byteOffset->toInt());

        $node = $this->parser->parseSourceFile($truncatedSource)->getDescendantNodeAtPosition(
            // the parser requires the byte offset, not the char offset
            strlen($truncatedSource)
        );

        $isComplete = true;

        foreach ($this->tolerantCompletors as $tolerantCompletor) {
            $completionNode = $node;

            if ($tolerantCompletor instanceof TolerantQualifiable) {
                $completionNode = $tolerantCompletor->qualifier()->couldComplete($node);
            }

            if (!$completionNode) {
                continue;
            }

            $suggestions = $tolerantCompletor->complete($completionNode, $source, $byteOffset);
            foreach ($suggestions as $suggestion) {
                yield $this->resolveClassSuggestion($completionNode, $suggestion);
            }

            $isComplete = $isComplete && $suggestions->getReturn();
        }

        return $isComplete;
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

    private function filterNonQualifyingClasses(Node $node): array
    {
        return array_filter($this->tolerantCompletors, function (TolerantCompletor $completor) use ($node) {
            if (!$completor instanceof TolerantQualifiable) {
                return true;
            }

            return $completor->qualifier()->couldComplete($node);
        });
    }

    private function resolveClassSuggestion(Node $completionNode, Suggestion $suggestion): Suggestion
    {
        if (Suggestion::TYPE_CLASS !== $suggestion->type()) {
            return $suggestion;
        }

        /** @var ResolvedName[] $importTable */
        [$importTable] = $completionNode->getImportTablesForCurrentScope();

        // Prioritize import without alias
        if (isset($importTable[$suggestion->name()])) {
            return $suggestion->withoutNameImport();
        }

        $suggestionFqcn = $suggestion->classImport();
        $possibleMatches = [];
        foreach ($importTable as $alias => $resolvedName) {
            $importFqcn = $resolvedName->getFullyQualifiedNameText();

            if ($suggestionFqcn === $importFqcn) {
                return $suggestion->withoutNameImport()->withName($alias);
            }

            if (0 === strpos($suggestionFqcn, $importFqcn)) {
                $possibleMatches[$alias] = $importFqcn;
            }
        }

        if (!$possibleMatches) {
            return $suggestion;
        }

        // Sort the possible by matches by FQCN length
        uasort($possibleMatches, function (string $firstFqcn, $secondFqcn) {
            return strlen($firstFqcn) <=> strlen($secondFqcn);
        });

        // Keep the match with the longest FQCN (more accurate one)
        $importFqcn = end($possibleMatches);
        $alias = array_key_last($possibleMatches);
        $name = $alias.substr($suggestionFqcn, strlen($importFqcn));

        return $suggestion->withoutNameImport()->withName($name);
    }
}
