<?php

namespace Phpactor\Completion\Tests\Unit\Bridge\TolerantParser;

use Generator;
use Microsoft\PhpParser\Node;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\LimitingCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
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
            self::EXAMPLE_SOURCE,
            self::EXAMPLE_OFFSET
        )->will(function () {
            return;
            yield;
        });

        $suggestions = $this->create(10)->complete(
            $this->node->reveal(),
            self::EXAMPLE_SOURCE,
            self::EXAMPLE_OFFSET
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
            self::EXAMPLE_SOURCE,
            self::EXAMPLE_OFFSET
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
            self::EXAMPLE_SOURCE,
            self::EXAMPLE_OFFSET
        );

        $this->assertCount(2, $suggestions);
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
            self::EXAMPLE_SOURCE,
            self::EXAMPLE_OFFSET
        )->will(function () use ($suggestions) {
            foreach ($suggestions as $suggestion) {
                yield $suggestion;
            }
        });
    }
}
