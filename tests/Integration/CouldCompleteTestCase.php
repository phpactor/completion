<?php

namespace Phpactor\Completion\Tests\Integration;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\CouldComplete;
use Phpactor\Completion\Response;
use Phpactor\TestUtils\ExtractOffset;

abstract class CouldCompleteTestCase extends TestCase
{
    abstract protected function createCompletor(string $source): CouldComplete;

    abstract public function provideComplete(): Generator;

    abstract public function provideCouldComplete(): Generator;

    abstract public function provideCouldNotComplete(): Generator;

    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $result = $completor->complete($source, $offset);

        $canComplete = $completor->couldComplete($source, $offset);
        $this->assertTrue($canComplete);
        $result = $completor->complete($source, $offset);

        $this->assertEquals($expected, $result->suggestions()->toArray());
        $this->assertEquals(json_encode($expected, true), json_encode($result->suggestions()->toArray(), true));
    }

    /**
     * @dataProvider provideCouldComplete
     */
    public function testCouldComplete(string $source)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $result = $completor->couldComplete($source, $offset);

        $this->assertEquals(true, $result);
    }

    /**
     * @dataProvider provideCouldNotComplete
     */
    public function testCouldNotComplete(string $source)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $result = $completor->couldComplete($source, $offset);

        $this->assertEquals(false, $result);
    }

}
