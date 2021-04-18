<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\ReferenceFinder;

use Generator;
use Phpactor\Completion\Bridge\TolerantParser\ReferenceFinder\NameSearcherCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Formatter\NameSearchResultFunctionSnippetFormatter;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ReflectorBuilder;

class NameSearcherCompletorTest extends TolerantCompletorTestCase
{
    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected): void
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

        yield 'function' => [
            '<?php function bar($foo) {}; ba<>', [
                [
                    'type'              => Suggestion::TYPE_FUNCTION,
                    'name'              => 'bar',
                    'short_description' => 'bar',
                    'snippet'           => 'bar($foo)'
                ]
            ]
        ];
    }

    protected function nameSearchResultFunctionSnippetFormatter(
        Reflector $reflector
    ): NameSearchResultFunctionSnippetFormatter {
        return new NameSearchResultFunctionSnippetFormatter(
            $this->formatter(),
            $reflector
        );
    }

    protected function createTolerantCompletor(TextDocument $source): TolerantCompletor
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();

        $searcher = $this->prophesize(NameSearcher::class);
        $searcher->search('Foo')->willYield([
            NameSearchResult::create('class', 'Foobar')
        ]);
        $searcher->search('ba')->willYield([
           NameSearchResult::create('function', 'bar'),
       ]);

        return new NameSearcherCompletor(
            $searcher->reveal(),
            $this->nameSearchResultFunctionSnippetFormatter($reflector)
        );
    }
}
