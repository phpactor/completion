<?php

namespace Phpactor\Completion\Tests\Integration;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Response;
use Phpactor\TestUtils\ExtractOffset;

abstract class CompletorTestCase extends TestCase
{
    abstract protected function createCompletor(string $source): Completor;

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

        $this->assertEquals($expected, $result->suggestions()->toArray());
        $this->assertEquals(json_encode($expected, true), json_encode($result->suggestions()->toArray(), true));
    }

}
