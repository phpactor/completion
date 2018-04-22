<?php

namespace Phpactor\Completion\Tests\Integration\Completor;

use Phpactor\Completion\Tests\Integration\CompletorTestCase;
use Phpactor\Completion\Core\Completor;
use Generator;
use Phpactor\Completion\Adapter\WorseReflection\Completor\WorseLocalVariableCompletor;
use Phpactor\WorseReflection\ReflectorBuilder;

class LocalVariableCompletorTest extends CompletorTestCase
{
    protected function createCompletor(string $source): Completor
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new WorseLocalVariableCompletor($reflector, null, $this->formatter());
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
                    'name' => 'foobar',
                    'info' => 'string',
                ],
                [
                    'type' => 'v',
                    'name' => 'barfoo',
                    'info' => 'int',
                ],
            ]
        ];

        yield 'Complete previously declared variable which had no type' => [
            <<<'EOT'
<?php

$callMe = foobar();

/** @var Barfoo $callMe */
$callMe = foobar();

$call<>

EOT
            , [
                [
                    'type' => 'v',
                    'name' => 'callMe',
                    'info' => 'Barfoo',
                ],
            ],
        ];

        yield 'Does not assign offer suggestion for incomplete assignment' => [
            <<<'EOT'
<?php

$std = new \stdClass();
$std = $st<>

EOT
            , [
                [
                    'type' => 'v',
                    'name' => 'std',
                    'info' => 'stdClass',
                ],
            ],
        ];

        yield 'Provides contextual information when completing a method call' => [
            <<<'EOT'
<?php

class Foobar { public function bar(string $foo, $bar) {} }

$hello = 'hello';
$foo = new Foobar();
$foo->bar($<>

EOT
            , [
                [
                    'type' => 'v',
                    'name' => 'hello',
                    'info' => 'string#(>>string $foo<<, $bar)',
                ],
            ],
        ];
    }
}
