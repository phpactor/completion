<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser;

use Generator;
use Phpactor\Completion\Bridge\TolerantParser\AnnotationCompletor;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\CompletorTestCase;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;
use Phpactor\TextDocument\TextDocumentBuilder;
use Prophecy\Argument;

class AnnotationCompletorTest extends CompletorTestCase
{
    protected function createCompletor(string $source): Completor
    {
        $source = TextDocumentBuilder::create($source)->uri('file:///tmp/test')->build();

        $searcher = $this->prophesize(NameSearcher::class);
        $searcher->search(Argument::any())->willYield([]);
        $searcher->search('Ann')->willYield([
            NameSearchResult::create('class', 'Annotation')
        ]);

        return new AnnotationCompletor($searcher->reveal());
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
        yield 'not a docblock' => [
            <<<'EOT'
<?php

/*
 * @Ann<>
 */
class Foo {}
EOT
            , []
        ];

        yield 'not a text annotation' => [
            <<<'EOT'
<?php

/**
 * Ann<>
 */
class Foo {}
EOT
            , []
        ];

        yield 'annotation on node in the middle of the AST' => [
            <<<'EOT'
<?php

class Foo
{
    /**
     * @var string
     */
    private $foo;

    /**
     * @Ann<>
     */
    public function foo(): string
    {
        return 'foo';
    }

    public function bar(): string
    {
        return 'bar';
    }
}
EOT
        , [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Annotation',
                    'short_description' => 'Annotation',
                ]
        ]];
    }
}
