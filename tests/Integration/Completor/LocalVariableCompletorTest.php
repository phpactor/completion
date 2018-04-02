<?php

namespace Phpactor\Completion\Tests\Integration\Completor;

use Phpactor\Completion\Tests\Integration\CouldCompleteTestCase;
use Phpactor\Completion\Core\CouldComplete;
use Generator;
use Phpactor\Completion\Adapter\WorseReflection\Completor\WorseLocalVariableCompletor;
use Phpactor\WorseReflection\ReflectorBuilder;

class LocalVariableCompletorTest extends CouldCompleteTestCase
{
    protected function createCompletor(string $source): CouldComplete
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new WorseLocalVariableCompletor($reflector);
    }

    public function provideCouldComplete(): Generator
    {
        yield 'for variable name' => [ '<?php echo $<>;' ];
        yield 'for partially complete variable name' => [ '<?php echo $foo<>;' ];
        yield 'for assignment' => [ '<?php $foo=$<>;' ];
        yield 'for array declaration' => [ '<?php $hello  = [$<>' ];
        yield 'for function call' => [ '<?php $hello  = foobar($<>' ];
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'empty string' => [ '<?php  <>' ];
        yield 'function call' => [ '<?php echo<>' ];
        yield 'variable with space' => [ '<?php $foo <>' ];
        yield 'static variable' => ['<?php Foobar::$<>'];
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
                    'name' => 'foobar',
                    'info' => 'string',
                ]
            ]
        ];

        yield 'Partial variable' => [
            '<?php $barfoo = "goodbye"; $foobar = "hello"; $foo<>',
            [
                [
                    'type' => 'v',
                    'name' => 'foobar',
                    'info' => 'string',
                ]
            ]
        ];

        yield 'Variables' => [
            '<?php $barfoo = 12; $foobar = "hello"; $<>',
            [
                [
                    'type' => 'v',
                    'name' => 'barfoo',
                    'info' => 'int',
                ],
                [
                    'type' => 'v',
                    'name' => 'foobar',
                    'info' => 'string',
                ]
            ]
        ];
    }
}
