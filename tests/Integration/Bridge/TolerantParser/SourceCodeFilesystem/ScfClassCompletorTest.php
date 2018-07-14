<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\SourceCodeFilesystem;

use Generator;
use Phpactor\ClassFileConverter\Adapter\Simple\SimpleFileToClass;
use Phpactor\Completion\Bridge\TolerantParser\SourceCodeFilesystem\ScfClassCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Filesystem\Domain\FilePath;

class ScfClassCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(string $source): TolerantCompletor
    {
        $filesystem = new SimpleFilesystem(FilePath::fromString(__DIR__ . '/files'));
        $fileToClass = new SimpleFileToClass();

        return new ScfClassCompletor($filesystem, $fileToClass);
    }

    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        $this->assertComplete($source, $expected);
    }

    public function provideComplete(): Generator
    {
        yield 'extends' => [
            '<?php class Foobar extends <>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Alphabet',
                    'info' => 'Test\Name\Alphabet',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'info' => 'Test\Name\Backwards',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'info' => 'Test\Name\Clapping',
                ],
            ],
        ];

        yield 'extends partial' => [
            '<?php class Foobar extends Cl<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'info' => 'Test\Name\Clapping',
                ],
            ],
        ];

        yield 'extends keyword with subsequent code' => [
            '<?php class Foobar extends Cl<> { }',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'info' => 'Test\Name\Clapping',
                ],
            ],
        ];

        yield 'new keyword' => [
            '<?php new <>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Alphabet',
                    'info' => 'Test\Name\Alphabet',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'info' => 'Test\Name\Backwards',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'info' => 'Test\Name\Clapping',
                ],
            ],
        ];

        yield 'new keyword with partial' => [
            '<?php new Cla<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'info' => 'Test\Name\Clapping',
                ],
            ],
        ];

        yield 'use keyword' => [
            '<?php use <>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Alphabet',
                    'info' => 'Test\Name\Alphabet',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'info' => 'Test\Name\Backwards',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'info' => 'Test\Name\Clapping',
                ],
            ],
        ];

        yield 'use keyword with partial' => [
            '<?php use Cla<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'info' => 'Test\Name\Clapping',
                ],
            ],
        ];
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'non member access' => [ '<?php $hello<>' ];
        yield 'variable with previous accessor' => [ '<?php $foobar->hello; $hello<>' ];
        yield 'variable with previous accessor' => [ '<?php $foobar->hello; $hello<>' ];
        yield 'statement with previous member access' => [ '<?php if ($foobar && $this->foobar) { echo<>' ];
        yield 'variable with previous static member access' => [ '<?php Hello::hello(); $foo<>' ];
    }
}
