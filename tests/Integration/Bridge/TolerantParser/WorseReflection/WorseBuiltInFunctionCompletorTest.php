<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseBuiltInFunctionCompletor;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\WorseReflection\Core\Logger\ArrayLogger;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StringSourceLocator;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Completion\Core\ChainCompletor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Tests\Integration\CompletorTestCase;
use Generator;
use Phpactor\Completion\Core\Completor;
use Phpactor\TestUtils\ExtractOffset;
use PhpBench\Benchmark\Metadata\Annotations\Subject;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;

class WorseBuiltInFunctionCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(string $source): TolerantCompletor
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();

        return new WorseBuiltInFunctionCompletor($reflector, $this->formatter());
    }

    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        $this->assertComplete($source, $expected);
    }

    /**
     * @dataProvider provideCouldNotComplete
     */
    public function testCouldNotComplete(string $source)
    {
        $this->assertCouldNotComplete($source);
    }

    public function provideComplete(): Generator
    {
        yield 'function name' => [ 
            '<?php function strpos ($haystack, $needle, $offset = 0):int {}; str<>', [
                [
                    'type' => 'f',
                    'name' => 'strpos',
                    'info' => 'strpos($haystack, $needle, $offset = 0): int',
                ]
            ]
        ];
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'non member access' => [ '<?php $hello<>' ];
    }
}
