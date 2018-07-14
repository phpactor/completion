<?php

namespace Phpactor\Completion\Tests\Integration;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\FunctionFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\MethodFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\ParametersFormatter;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\ParameterFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\PropertyFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\TypeFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\TypesFormatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\VariableFormatter;
use Phpactor\Completion\Adapter\WorseReflection\Formatter\VariableWithNodeFormatter;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Response;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\WorseReflection\ReflectorBuilder;

abstract class CompletorTestCase extends TestCase
{
    abstract protected function createCompletor(string $source): Completor;

    protected function formatter(): ObjectFormatter
    {
        return new ObjectFormatter([
            new TypeFormatter(),
            new TypesFormatter(),
            new FunctionFormatter(),
            new MethodFormatter(),
            new ParameterFormatter(),
            new ParametersFormatter(),
            new PropertyFormatter(),
            new VariableFormatter(),
        ]);
    }

    protected function assertComplete(string $source, array $expected)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $result = $completor->complete($source, $offset);

        $actual = $result->suggestions()->toArray();

        foreach ($expected as $index => $expectedSuggestion) {
            $this->assertArraySubset($expectedSuggestion, $actual[$index]);
        }

        $this->assertCount(count($expected), $actual);
    }

    protected function assertCompletionErrors(string $source, array $expected)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $results = $completor->complete($source, $offset);
        $this->assertEquals($expected, $results->issues()->toArray());
    }

    public function assertCouldNotComplete(string $source)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $result = $completor->complete($source, $offset);

        $this->assertEquals(Response::new(), $result);
    }

}
