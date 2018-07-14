<?php

namespace Phpactor\Completion\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\ChainCompletor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Completor;
use Prophecy\Prophecy\ObjectProphecy;
use Phpactor\Completion\Core\Suggestions;
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

    public function testReturnsEmptyResponseWithNoCompletors()
    {
        $completor = $this->create([]);
        $response = $completor->complete(self::TEST_SOURCE, self::TEST_OFFSET);

        $this->assertEquals(Response::new(), $response);
    }

    public function testReturnsEmptyResponseWhenCompletorCouldNotComplete()
    {
        $completor = $this->create([
            $this->completor1->reveal()
        ]);

        $this->completor1->complete(self::TEST_SOURCE, self::TEST_OFFSET)
            ->shouldBeCalled()
            ->willReturn(Response::new());

        $response = $completor->complete(self::TEST_SOURCE, self::TEST_OFFSET);

        $this->assertEquals(Response::new(), $response);
    }

    public function testReturnsSuggestionsFromCompletor()
    {
        $expected = Response::fromSuggestions(
            Suggestions::fromSuggestions([
                Suggestion::create('foobar')
            ])
        );
        $completor = $this->create([
            $this->completor1->reveal()
        ]);

        $this->completor1->complete(self::TEST_SOURCE, self::TEST_OFFSET)
            ->shouldBeCalled()
            ->willReturn($expected);

        $response = $completor->complete(self::TEST_SOURCE, self::TEST_OFFSET);

        $this->assertEquals($expected, $response);
    }

    /**
     * @var CouldComplete[] $completors
     */
    public function create(array $completors): ChainCompletor
    {
        return new ChainCompletor($completors);
    }
}
