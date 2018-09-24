<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Parser;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\Completion\Core\Util\OffsetHelper;

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

    public function complete(string $source, int $byteOffset): Response
    {
        $truncatedSource = $this->truncateSource($source, $byteOffset);

        $node = $this->parser->parseSourceFile($truncatedSource)->getDescendantNodeAtPosition(
            // the parser requires the byte offset, not the char offset
            strlen($truncatedSource)
        );

        $response = Response::new();

        foreach ($this->tolerantCompletors as $tolerantCompletor) {
            $completionNode = $node;

            if ($tolerantCompletor instanceof TolerantQualifiable) {
                $completionNode = $tolerantCompletor->qualifier()->couldComplete($node);
            }

            if (!$completionNode) {
                continue;
            }

            $response->merge($tolerantCompletor->complete($completionNode, $source, $byteOffset));
        }

        return $response;
    }

    private function truncateSource(string $source, int $byteOffset)
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

    private function filterNonQualifyingClasses(Node $node)
    {
        return array_filter($this->tolerantCompletors, function (TolerantCompletor $completor) use ($node) {
            if (!$completor instanceof TolerantQualifiable) {
                return true;
            }

            return $completor->qualifier()->couldComplete($node);
        });
    }
}
