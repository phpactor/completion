<?php

namespace Phpactor\Completion\Tests\Integration;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\CouldComplete;
use Phpactor\Completion\Response;
use Phpactor\TestUtils\ExtractOffset;

abstract class CouldCompleteTestCase extends TestCase
{
    abstract public function provideComplete(): Generator;
    abstract public function provideCouldComplete(): Generator;
    abstract protected function createCompletor(string $source): CouldComplete;

    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);

        $completor->couldComplete($source, $offset);
        $result = $completor->complete($source, $offset);

        $this->assertEquals($expected, $result->suggestions()->toArray());
        $this->assertEquals(json_encode($expected, true), json_encode($result->suggestions()->toArray(), true));
    }

    /**
     * @dataProvider provideCouldComplete
     */
    public function testCouldComplete(string $source, bool $shouldComplete)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $result = $completor->couldComplete($source, $offset);

        $this->assertEquals($shouldComplete, $result);
    }

}
