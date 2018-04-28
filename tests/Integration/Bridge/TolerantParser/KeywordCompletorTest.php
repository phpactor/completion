<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser;

use Generator;
use Phpactor\Completion\Bridge\TolerantParser\KeywordCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;

class KeywordCompletorTest extends TolerantCompletorTestCase
{
    public function provideComplete(): Generator
    {
        yield 'Nothing' => [
            '<?php $<>', []
        ];

        yield 'Class base clause' => [
            '<?php class Foobar <>', [
                [
                    'type' => 'k',
                    'name' => 'extends ',
                    'info' => '',
                ],
                [
                    'type' => 'k',
                    'name' => 'implements ',
                    'info' => '',
                ]
            ]
        ];
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'variable' => [ '<?php $hello<>' ];
    }

    protected function createTolerantCompletor(string $source): TolerantCompletor
    {
        return new KeywordCompletor();
    }
}
