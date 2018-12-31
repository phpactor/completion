<?php

namespace Phpactor\Completion\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\ChainCompletor;
use Phpactor\Completion\Core\Completor;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentBuilder;
use Prophecy\Prophecy\ObjectProphecy;
use Phpactor\Completion\Core\Suggestion;

class CompletorTest extends TestCase
{
    /**
     * @var ObjectProphecy|CouldComplete
     */
    private $completor1;

    const EXAMPLE_SOURCE = 'test source';
    const EXAMPLE_OFFSET = 1234;

    public function setUp()
    {
        $this->completor1 = $this->prophesize(Completor::class);
    }

    public function testEmptyGeneratorWithNoCompletors()
    {
        $completor = $this->create([]);
        $suggestions = $completor->complete($this->textDocument(self::EXAMPLE_SOURCE), ByteOffset::fromInt(self::EXAMPLE_OFFSET));

        $this->assertCount(0, $suggestions);
    }

    public function testReturnsEmptyGeneratorWhenCompletorCouldNotComplete()
    {
        $completor = $this->create([
            $this->completor1->reveal()
        ]);

        $this->completor1->complete($this->textDocument(self::EXAMPLE_SOURCE), ByteOffset::fromInt(self::EXAMPLE_OFFSET))
            ->shouldBeCalled()
            ->will(function () {
                return;
                yield;
            });

        $suggestions = iterator_to_array($completor->complete($this->textDocument(self::EXAMPLE_SOURCE), ByteOffset::fromInt(self::EXAMPLE_OFFSET)));

        $this->assertCount(0, $suggestions);
    }

    public function testReturnsSuggestionsFromCompletor()
    {
        $expected = [
            Suggestion::create('foobar')
        ];

        $completor = $this->create([
            $this->completor1->reveal()
        ]);

        $this->completor1->complete($this->textDocument(self::EXAMPLE_SOURCE), ByteOffset::fromInt(self::EXAMPLE_OFFSET))
            ->shouldBeCalled()
            ->will(function () use ($expected) {
                foreach ($expected as $suggestion) {
                    yield $suggestion;
                }
            });

        $suggestions = iterator_to_array($completor->complete($this->textDocument(self::EXAMPLE_SOURCE), ByteOffset::fromInt(self::EXAMPLE_OFFSET)));

        $this->assertEquals($expected, $suggestions);
    }

    /**
     * @var CouldComplete[] $completors
     */
    public function create(array $completors): ChainCompletor
    {
        return new ChainCompletor($completors);
    }

    private function textDocument(string $document): TextDocument
    {
        return TextDocumentBuilder::create($document)->build();
    }
}
