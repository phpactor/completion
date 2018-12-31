<?php

namespace Phpactor\Completion\Tests\Unit\Bridge\TolerantParser;

use Microsoft\PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\LimitingCompletor;
use Phpactor\Completion\Bridge\TolerantParser\Qualifier\AlwaysQualfifier;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantQualifiable;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentBuilder;
use Prophecy\Prophecy\ObjectProphecy;

class LimitingCompletorTest extends TestCase
{
    const EXAMPLE_SOURCE = '<?php';
    const EXAMPLE_OFFSET = 15;

    /**
     * @var ObjectProphecy
     */
    private $innerCompletor;

    /**
     * @var ObjectProphecy
     */
    private $node;

    public function setUp()
    {
        $this->innerCompletor = $this->prophesize(TolerantCompletor::class);
        $this->node = $this->prophesize(Node::class);
    }

    public function testNoSuggestions()
    {
        $this->innerCompletor->complete(
            $this->node->reveal(),
            $this->textDocument(self::EXAMPLE_SOURCE),
            ByteOffset::fromInt(self::EXAMPLE_OFFSET)
        )->will(function () {
            return;
            yield;
        });

        $suggestions = $this->create(10)->complete(
            $this->node->reveal(),
            $this->textDocument(self::EXAMPLE_SOURCE),
            ByteOffset::fromInt(self::EXAMPLE_OFFSET)
        );

        $this->assertCount(0, $suggestions);
    }

    public function testSomeSuggestions()
    {
        $suggestions = [
            $this->suggestion('foobar'),
            $this->suggestion('barfoo'),
            $this->suggestion('carfoo'),
        ];

        $this->primeInnerCompletor($suggestions);

        $suggestions = $this->create(10)->complete(
            $this->node->reveal(),
            $this->textDocument(self::EXAMPLE_SOURCE),
            ByteOffset::fromInt(self::EXAMPLE_OFFSET)
        );

        $this->assertCount(3, $suggestions);
    }

    public function testAppliesLimit()
    {
        $suggestions = [
            $this->suggestion('foobar'),
            $this->suggestion('barfoo'),
            $this->suggestion('carfoo'),
        ];

        $this->primeInnerCompletor($suggestions);

        $suggestions = $this->create(2)->complete(
            $this->node->reveal(),
            $this->textDocument(self::EXAMPLE_SOURCE),
            ByteOffset::fromInt(self::EXAMPLE_OFFSET)
        );

        $this->assertCount(2, $suggestions);
    }

    public function testQualifiesNonQualifiableCompletors()
    {
        $completor = $this->create(10);
        $node = $this->prophesize(Node::class);

        $qualified = $completor->qualifier()->couldComplete($node->reveal());
        $this->assertSame($node->reveal(), $qualified);
    }

    public function testPassesThroughToInnerQualifier()
    {
        $node = $this->prophesize(Node::class);
        $this->innerCompletor->willImplement(TolerantQualifiable::class);
        $this->innerCompletor->qualifier()->willReturn(new AlwaysQualfifier())->shouldBeCalled();
        $completor = $this->create(10);

        $qualified = $completor->qualifier()->couldComplete($node->reveal());
        $this->assertSame($node->reveal(), $qualified);
    }

    private function create(int $limit): LimitingCompletor
    {
        return new LimitingCompletor($this->innerCompletor->reveal(), $limit);
    }

    private function suggestion(string $name)
    {
        return Suggestion::create($name);
    }

    private function primeInnerCompletor(array $suggestions)
    {
        $this->innerCompletor->complete(
            $this->node->reveal(),
            $this->textDocument(self::EXAMPLE_SOURCE),
            ByteOffset::fromInt(self::EXAMPLE_OFFSET)
        )->will(function () use ($suggestions) {
            foreach ($suggestions as $suggestion) {
                yield $suggestion;
            }
        });
    }

    private function textDocument(string $document): TextDocument
    {
        return TextDocumentBuilder::create($document)->build();
    }
}
