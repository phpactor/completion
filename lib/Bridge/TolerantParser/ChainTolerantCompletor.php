<?php

namespace Phpactor\Completion\Bridge\TolerantParser;

use Microsoft\PhpParser\Parser;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestions;

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
        $node = $this->parser->parseSourceFile($source)->getDescendantNodeAtPosition($this->rewindToLastNonWhitespaceChar($source, $offset));
        $response = Response::new();

        // it isn't possible that getDescendantNodeAtPosition returns null, but
        // the docblock says it will, see
        // https://github.com/Microsoft/tolerant-php-parser/pull/242/files
        if (null === $node) {
            return $suggestions;
        }

        foreach ($this->tolerantCompletors as $tolerantCompletor) {
            $response->merge($tolerantCompletor->complete($node, $source, $offset));
        }

        return $response;
    }

    private function rewindToLastNonWhitespaceChar(string $source, int $offset)
    {
        while (!isset($source[$offset]) || $source[$offset] == ' ' || $source[$offset] == PHP_EOL) {
            $offset--;
        }

        return $offset;
    }
}
