<?php

namespace Phpactor\Completion\Tests\Integration\Completor;

use Phpactor\Completion\Tests\Integration\CouldCompleteTestCase;
use Phpactor\Completion\CouldComplete;
use Generator;
use Phpactor\Completion\Completor\LocalVariableCompletor;
use Phpactor\WorseReflection\ReflectorBuilder;

class LocalVariableCompletorTest extends CouldCompleteTestCase
{
    protected function createCompletor(string $source): CouldComplete
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new LocalVariableCompletor($reflector);
    }

    public function provideCouldComplete(): Generator
    {
        yield 'for variable name' => [ 'echo $<>;' ];
        yield 'for partially complete variable name' => [ 'echo $foo<>;' ];
        yield 'for assignment' => [ '$foo=$<>;' ];
        yield 'for array declaration' => [ '$hello  = [$<>' ];
        yield 'for function call' => [ '$hello  = foobar($<>' ];
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'empty string' => [ ' <>' ];
        yield 'function call' => [ 'echo<>' ];
        yield 'variable with space' => [ '$foo <>' ];
        yield 'static variable' => ['Foobar::$<>'];
    }

    public function provideComplete(): Generator
    {
        yield 'Nothing' => [
            '<?php $<>', []
        ];

        yield 'Variable' => [
            '<?php $foobar = "hello"; $<>',
            [
                [
                    'type' => 'v',
                    'name' => '$foobar',
                    'info' => 'string',
                ]
            ]
        ];

        yield 'Variables' => [
            '<?php $barfoo = 12; $foobar = "hello"; $<>',
            [
                [
                    'type' => 'v',
                    'name' => '$barfoo',
                    'info' => 'int',
                ],
                [
                    'type' => 'v',
                    'name' => '$foobar',
                    'info' => 'string',
                ]
            ]
        ];
    }
}
