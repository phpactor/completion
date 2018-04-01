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
        yield 'variable name' => [ 'echo $<>;' ];
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'empty string' => [ ' <>' ];
        yield 'function call' => [ 'echo<>' ];
    }

    public function provideComplete(): Generator
    {
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
    }
}
