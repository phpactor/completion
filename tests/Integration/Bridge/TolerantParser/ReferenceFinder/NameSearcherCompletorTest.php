<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\ReferenceFinder;

use Generator;
use Phpactor\Completion\Bridge\TolerantParser\ReferenceFinder\NameSearcherCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor as PhpactorNameSearcherCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\TextDocument\TextDocument;
use Prophecy\Argument;

class NameSearcherCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(TextDocument $source): TolerantCompletor
    {
        $nameSearcherCompletor = $this->prophesize(PhpactorNameSearcherCompletor::class);
        $nameSearcherCompletor->complete(Argument::cetera())->will(function () {
            yield Suggestion::createWithOptions('Foobar', [
                'type' => Suggestion::TYPE_CLASS,
                'short_description' => 'Foobar',
                'name_import' => 'Foobar',
            ]);

            return true;
        });

        return new NameSearcherCompletor(
            $nameSearcherCompletor->reveal(),
        );
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
