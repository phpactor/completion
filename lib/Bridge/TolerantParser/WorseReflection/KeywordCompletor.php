<?php

declare(strict_types=1);

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\TokenStringMaps;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

class KeywordCompletor implements TolerantCompletor
{
    /**
    * {@inheritDoc}
    */
    public function complete(Node $node, TextDocument $source, ByteOffset $offset): Generator
    {
        
        // TokenStringMaps::RESERVED_WORDS
        foreach (array_merge(array_keys(TokenStringMaps::RESERVED_WORDS), array_keys(TokenStringMaps::KEYWORDS)) as $keyword) {
            yield Suggestion::createWithOptions(
                $keyword,
                [
                    'type' => Suggestion::TYPE_KEYWORD
                ]
            );
        }
        return true;
    }
}
