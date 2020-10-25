<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Parser;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor;
use Phpactor\Completion\Core\Util\OffsetHelper;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\Util\WordAtOffset;

class AnnotationCompletor implements Completor
{
    use NameSearcherCompletor;

    /**
     * @var NameSearcher
     */
    private $nameSearcher;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(NameSearcher $nameSearcher, Parser $parser = null)
    {
        $this->nameSearcher = $nameSearcher;
        $this->parser = $parser ?: new Parser();
    }

    public function complete(TextDocument $source, ByteOffset $byteOffset): Generator
    {
        $truncatedSource = $this->truncateSource((string) $source, $byteOffset->toInt());
        $sourceNodeFile = $this->parser->parseSourceFile((string) $source);

        $node = $this->findNodeForPhpdocAtPosition(
            $sourceNodeFile,
            // the parser requires the byte offset, not the char offset
            strlen($truncatedSource)
        );

        $isComplete = true;

        if (!$node) {
            // Ignore this case is the cursor is not in a phpdoc block
            return $isComplete;
        }

        $annotation = WordAtOffset::annotation($source, $byteOffset->toInt());

        if (0 !== strpos($annotation, '@')) {
            // Ignore if not an annotation
            return $isComplete;
        }

        $suggestions = $this->completeName(ltrim($annotation, '@'));

        // TODO: filter suggestions for class having @Annotation only
        yield from $suggestions;

        return $isComplete && $suggestions->getReturn();
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

    protected function getSearcher(): NameSearcher
    {
        return $this->nameSearcher;
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
}
