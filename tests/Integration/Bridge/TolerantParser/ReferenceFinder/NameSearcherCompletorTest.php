<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\ReferenceFinder;

use Generator;
use Phpactor\Completion\Bridge\TolerantParser\ReferenceFinder\NameSearcherCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;
use Phpactor\TextDocument\TextDocument;
use Prophecy\Argument;

class NameSearcherCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(TextDocument $source): TolerantCompletor
    {
        $searcher = $this->prophesize(NameSearcher::class);
        $searcher->search(Argument::any())->willYield([
            NameSearchResult::create('class', 'Foobar')
        ]);
        return new NameSearcherCompletor($searcher->reveal());
    }

    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        $this->assertComplete($source, $expected);
    }

    public function provideComplete(): Generator
    {
        yield 'class' => [
            '<?php class Foobar {} :int {}; new Foo<>', [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Foobar',
                    'short_description' => 'Foobar',
                ]
            ]
        ];
    }
}
