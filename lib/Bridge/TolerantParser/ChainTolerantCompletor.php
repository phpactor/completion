<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

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

    public function complete(string $source, int $offset): Response
    {
        // truncate source at offset - we don't want the rest of the source
        // file contaminating the completion (for example `$foo($<>\n    $bar =
        // ` will evaluate the Variable node as an expression node with a
        // double variable `$\n    $bar = `
        $truncatedSource = mb_substr($source, 0, $offset);
        $nonWhitespaceOffset = OffsetHelper::lastNonWhitespaceOffset($truncatedSource);
        $truncatedSource = mb_substr($source, 0, $nonWhitespaceOffset);

        $node = $this->parser->parseSourceFile($truncatedSource)->getDescendantNodeAtPosition(
            // the parser requires the byte offset, not the char offset
            strlen($truncatedSource)
        );
        $response = Response::new();

        foreach ($this->tolerantCompletors as $tolerantCompletor) {
            $response->merge($tolerantCompletor->complete($node, $source, $offset));
        }

        return $response;
    }
}
