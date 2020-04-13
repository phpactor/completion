<?php

namespace Phpactor\Completion\Tests\Unit\Core\Completor;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Completor\ArrayCompletor;
use Phpactor\Completion\Core\Completor\DedupeCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class DedupeCompletorTest extends TestCase
{
    public function testDeduplicates()
    {
        $source = TextDocumentBuilder::create('foobar')->build();
        $offset = ByteOffset::fromInt(10);

        $inner = new ArrayCompletor([
            Suggestion::create('foobar'),
            Suggestion::create('barfoo'),
            Suggestion::create('foobar'),
        ]);
        $dedupe = new DedupeCompletor($inner);
        self::assertEquals([
            Suggestion::create('foobar'),
            Suggestion::create('barfoo'),
        ], iterator_to_array($dedupe->complete($source, $offset)));
    }

    public function testDeduplicatesWithShortDescription()
    {
        $source = TextDocumentBuilder::create('foobar')->build();
        $offset = ByteOffset::fromInt(10);

        $inner = new ArrayCompletor([
            Suggestion::create('foobar'),
            Suggestion::createWithOptions('barfoo', [
                'short_description' => 'baf',
            ]),
            Suggestion::create('foobar'),
            Suggestion::createWithOptions('barfoo', [
                'short_description' => 'bosh',
            ]),
        ]);
        $dedupe = new DedupeCompletor($inner, true);
        self::assertEquals([
            Suggestion::create('foobar'),
            Suggestion::createWithOptions('barfoo', [
                'short_description' => 'baf',
            ]),
            Suggestion::createWithOptions('barfoo', [
                'short_description' => 'bosh',
            ]),
        ], iterator_to_array($dedupe->complete($source, $offset)));
    }
}
