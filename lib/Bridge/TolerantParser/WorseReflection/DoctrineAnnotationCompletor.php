<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Parser;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Util\OffsetHelper;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Reflector;

final class DoctrineAnnotationCompletor implements Completor
{
    /**
     * @var NameSearcherCompletor
     */
    private $nameCompletor;

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(
        NameSearcherCompletor $nameCompletor,
        Reflector $reflector,
        Parser $parser = null
    ) {
        $this->nameCompletor = $nameCompletor;
        $this->reflector = $reflector;
        $this->parser = $parser ?: new Parser();
    }

    public function complete(TextDocument $source, ByteOffset $byteOffset): Generator
    {
        $truncatedSource = $this->truncateSource((string) $source, $byteOffset->toInt());
        $sourceNodeFile = $this->parser->parseSourceFile((string) $source);

        $node = $this->findNodeForPhpdocAtPosition(
            $sourceNodeFile,
            // the parser requires the byte offset, not the char offset
            strlen($truncatedSource),
        );

        if (!$node) {
            // Ignore this case is the cursor is not in a phpdoc block
            return true;
        }

        if (!$annotation = $this->extractAnnotation($truncatedSource)) {
            // Ignore if not an annotation
            return true;
        }

        $suggestions = $this->nameCompletor->complete($source, $byteOffset, $annotation);

        foreach ($suggestions as $suggestion) {
            if (!$this->isAnAnnotation($suggestion)) {
                continue;
            }

            yield $suggestion->withSnippet($suggestion->name().'($1)$0');
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

    private function findNodeForPhpdocAtPosition(SourceFileNode $sourceNodeFile, int $position): ?Node
    {
        /** @var Node $node */
        foreach ($sourceNodeFile->getDescendantNodes() as $node) {
            if (
                $node->getFullStart() < $position
                && $position < $node->getStart()
            ) {
                // If the text is a phpdoc block return the node
                return $node->getDocCommentText() ? $node : null;
            }
        }

        return null;
    }

    private function isAnAnnotation(Suggestion $suggestion): bool
    {
        try {
            $reflectionClass = $this->reflector->reflectClass($suggestion->shortDescription());
            $docblock = $reflectionClass->docblock();

            return false !== strpos($docblock->raw(), '@Annotation');
        } catch (NotFound $error) {
            return false;
        }
    }

    private function extractAnnotation(string $truncatedSource): ?string
    {
        $count = 0;
        $annotation = preg_replace('/.*@([^@\s\t*]+)$/s', '$1', $truncatedSource, 1, $count);

        if (0 === $count) {
            return null;
        }

        return $annotation;
    }
}
