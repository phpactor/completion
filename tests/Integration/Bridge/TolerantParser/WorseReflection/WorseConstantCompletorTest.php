<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseConstantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseFunctionCompletor;
use Phpactor\Completion\Core\Suggestion;
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

class WorseConstantCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(string $source): TolerantCompletor
    {
        return new WorseConstantCompletor($this->formatter());
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
        define('PHPACTOR_TEST_FOO', 'Hello');
        yield 'constant' => [ 
            '<?php PHPACTOR_TEST_<>', [
                [
                    'type' => Suggestion::TYPE_CONSTANT,
                    'name' => 'PHPACTOR_TEST_FOO',
                    'info' => "PHPACTOR_TEST_FOO = 'Hello'",
                ]
            ]
        ];

        define('namespaced\PHPACTOR_NAMESPACED', 'Hello');
        yield 'namespaced constant' => [ 
            '<?php PHPACTOR_NAME<>', [
                [
                    'type' => Suggestion::TYPE_CONSTANT,
                    'name' => 'PHPACTOR_NAMESPACED',
                    'info' => "namespaced\PHPACTOR_NAMESPACED = 'Hello'",
                ]
            ]
        ];
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'non member access' => [ '<?php $hello<>' ];
    }
}
