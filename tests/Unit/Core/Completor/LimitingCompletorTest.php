<?php

namespace Phpactor\Completion\Tests\Unit\Core\Completor;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Completor\ArrayCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Completor\LimitingCompletor;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class LimitingCompletorTest extends TestCase
{
    public function testLimitsResults()
    {
        $source = TextDocumentBuilder::create('foobar')->build();
        $offset = ByteOffset::fromInt(10);

        $inner = new ArrayCompletor([
            Suggestion::create('foobar'),
            Suggestion::create('foobar'),
            Suggestion::create('foobar'),
            Suggestion::create('foobar'),
            Suggestion::create('foobar'),
        ]);
        $dedupe = new LimitingCompletor($inner, 2);
        self::assertEquals([
            Suggestion::create('foobar'),
            Suggestion::create('foobar'),
        ], iterator_to_array($dedupe->complete($source, $offset)));
    }
}
