<?php

namespace Phpactor\Completion\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\ChainCompletor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Completor;
use Prophecy\Prophecy\ObjectProphecy;
use Phpactor\Completion\Core\Suggestion;

class CompletorTest extends TestCase
{
    /**
     * @var ObjectProphecy|CouldComplete
     */
    private $completor1;

    const TEST_SOURCE = 'test source';
    const TEST_OFFSET = 1234;

    public function setUp()
    {
        $this->completor1 = $this->prophesize(Completor::class);
    }

    public function testEmptyGeneratorWithNoCompletors()
    {
        $completor = $this->create([]);
        $suggestions = $completor->complete(self::TEST_SOURCE, self::TEST_OFFSET);

        $this->assertCount(0, $suggestions);
    }

    public function testReturnsEmptyGeneratorWhenCompletorCouldNotComplete()
    {
        $completor = $this->create([
            $this->completor1->reveal()
        ]);

        $this->completor1->complete(self::TEST_SOURCE, self::TEST_OFFSET)
            ->shouldBeCalled()
            ->will(function () { return; yield; });

        $suggestions = iterator_to_array($completor->complete(self::TEST_SOURCE, self::TEST_OFFSET));

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

        $this->completor1->complete(self::TEST_SOURCE, self::TEST_OFFSET)
            ->shouldBeCalled()
            ->will(function () use ($expected) { foreach ($expected as $suggestion) { yield $suggestion; }});

        $suggestions = iterator_to_array($completor->complete(self::TEST_SOURCE, self::TEST_OFFSET));

        $this->assertEquals($expected, $suggestions);
    }

    /**
     * @var CouldComplete[] $completors
     */
    public function create(array $completors): ChainCompletor
    {
        return new ChainCompletor($completors);
    }
}
