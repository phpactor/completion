<?php

namespace Phpactor\Completion\Tests\Integration\Core\Completor;

use Phpactor\Completion\Core\Completor\CoreNameSearcherCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;
use Phpactor\TestUtils\PHPUnit\TestCase;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use Prophecy\Argument;

class CoreNameSearcherCompletorTest extends TestCase
{
    public function testComplete()
    {
        $searcher = $this->prophesize(NameSearcher::class);
        $searcher->search(Argument::any())->willYield([
            NameSearchResult::create('class', 'App\Foo\Bar')
        ]);
        $completor = new CoreNameSearcherCompletor($searcher->reveal());
        $expectedSuggestion = Suggestion::createWithOptions('Bar', [
            'type' => Suggestion::TYPE_CLASS,
            'short_description' => 'App\Foo\Bar',
            'name_import' => 'App\Foo\Bar'
        ]);
        $textDocument = TextDocumentBuilder::create('')->build();

        $generator = $completor->complete($textDocument, ByteOffset::fromInt(0), 'Foo');
        $suggestions = iterator_to_array($generator, false);

        $this->assertCount(1, $suggestions);
        $this->assertEquals($expectedSuggestion, $suggestions[0]);
    }
}
