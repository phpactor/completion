<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseFunctionCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\WorseReflection\ReflectorBuilder;
use Generator;

class WorseBuiltInFunctionCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(string $source): TolerantCompletor
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();

        return new WorseFunctionCompletor($reflector, $this->formatter());
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
        yield 'function with parameters' => [
            '<?php function mystrpos ($haystack, $needle, $offset = 0):int {}; mystrpos<>', [
                [
                    'type' => Suggestion::TYPE_FUNCTION,
                    'name' => 'mystrpos',
                    'short_description' => 'mystrpos($haystack, $needle, $offset = 0): int',
                ]
            ]
        ];

        yield 'namespaced function name' => [
            '<?php namespace foobar; function barfoo ():int {}; bar<> }', [
                [
                    'type' => Suggestion::TYPE_FUNCTION,
                    'name' => 'barfoo',
                    'short_description' => 'foobar\barfoo(): int',
                ]
            ]
        ];
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'non member access' => [ '<?php $hello<>' ];
        yield 'return value' => [ '<?php function barfoo() {}; class Hello { function barbar(): bar<>' ];
        yield 'parameter type' => [ '<?php function barfoo() {}; class Hello { function barbar(bar<>)' ];
    }
}
