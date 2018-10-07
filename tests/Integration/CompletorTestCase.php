<?php

namespace Phpactor\Completion\Tests\Integration;

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
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\TestUtils\ExtractOffset;

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
        $suggestions = iterator_to_array($completor->complete($source, $offset));
        usort($suggestions, function (Suggestion $suggestion1, Suggestion $suggestion2) {
            return $suggestion1->name() <=> $suggestion2->name();
        });

        $this->assertCount(count($expected), $suggestions);
        foreach ($expected as $index => $expectedSuggestion) {
            $this->assertArraySubset($expectedSuggestion, $suggestions[$index]->toArray());
        }

        $this->assertCount(count($expected), $suggestions);
    }

    public function assertCouldNotComplete(string $source)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $result = $completor->complete($source, $offset);

        $this->assertEmpty(iterator_to_array($result));
    }
}
