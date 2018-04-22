<?php

namespace Phpactor\Completion\Tests\Integration;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Adapter\WorseReflection\Formatter\MethodFormatter;
use Phpactor\Completion\Adapter\WorseReflection\Formatter\ObjectFormatter;
use Phpactor\Completion\Adapter\WorseReflection\Formatter\PropertyFormatter;
use Phpactor\Completion\Adapter\WorseReflection\Formatter\TypeFormatter;
use Phpactor\Completion\Adapter\WorseReflection\Formatter\TypesFormatter;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Response;
use Phpactor\TestUtils\ExtractOffset;

abstract class CompletorTestCase extends TestCase
{
    abstract protected function createCompletor(string $source): Completor;

    abstract public function provideComplete(): Generator;

    abstract public function provideCouldNotComplete(): Generator;

    protected function formatter(): ObjectFormatter
    {
        return new ObjectFormatter([
            new TypeFormatter(),
            new TypesFormatter(),
            new MethodFormatter(),
            new PropertyFormatter(),
        ]);
    }

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

    /**
     * @dataProvider provideCouldNotComplete
     */
    public function testCouldNotComplete(string $source)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $result = $completor->complete($source, $offset);

        $this->assertEquals(Response::new(), $result);
    }

}
