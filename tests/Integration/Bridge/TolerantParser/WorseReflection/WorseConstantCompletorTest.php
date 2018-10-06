<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseConstantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Generator;

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
                    'short_description' => "PHPACTOR_TEST_FOO = 'Hello'",
                ]
            ]
        ];

        define('namespaced\PHPACTOR_NAMESPACED', 'Hello');
        yield 'namespaced constant' => [
            '<?php PHPACTOR_NAME<>', [
                [
                    'type' => Suggestion::TYPE_CONSTANT,
                    'name' => 'PHPACTOR_NAMESPACED',
                    'short_description' => "namespaced\PHPACTOR_NAMESPACED = 'Hello'",
                ]
            ]
        ];
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'non member access' => [ '<?php $hello<>' ];
    }
}
