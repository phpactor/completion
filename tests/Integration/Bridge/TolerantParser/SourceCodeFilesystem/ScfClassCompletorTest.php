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
                    'short_description' => 'Test\Name\Alphabet',
                    'class_import' => 'Test\Name\Alphabet',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'short_description' => 'Test\Name\Backwards',
                    'class_import' => 'Test\Name\Backwards',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => 'Test\Name\Clapping',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'WithoutNS',
                    'short_description' => 'WithoutNS',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'extends when class already imported' => [
            '<?php use Test\Name\Alphabet; class Foobar extends <>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Alphabet',
                    'short_description' => 'Test\Name\Alphabet',
                    'class_import' => null,
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'short_description' => 'Test\Name\Backwards',
                    'class_import' => 'Test\Name\Backwards',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => 'Test\Name\Clapping',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'WithoutNS',
                    'short_description' => 'WithoutNS',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'extends when class from same namespace' => [
            '<?php namespace Test\Name; class Foobar extends <>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Alphabet',
                    'short_description' => 'Test\Name\Alphabet',
                    'class_import' => null,
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'short_description' => 'Test\Name\Backwards',
                    'class_import' => null,
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => null,
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'WithoutNS',
                    'short_description' => 'WithoutNS',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'extends partial' => [
            '<?php class Foobar extends Cl<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => 'Test\Name\Clapping',
                ],
            ],
        ];

        yield 'extends partial' => [
            '<?php class Foobar extends Wi<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'WithoutNS',
                    'short_description' => 'WithoutNS',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'extends partial with class already imported' => [
            '<?php  use Test\Name\Clapping;  class Foobar extends Cl<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'extends partial with class from same namespace' => [
            '<?php  namespace Test\Name;  class Foobar extends Cl<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'extends keyword with subsequent code' => [
            '<?php class Foobar extends Cl<> { }',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => 'Test\Name\Clapping',
                ],
            ],
        ];

        yield 'extends keyword with subsequent code, with class already imported' => [
            '<?php use Test\Name\Clapping; class Foobar extends Cl<> { }',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'extends keyword with subsequent code, with class from same namespace' => [
            '<?php namespace Test\Name; class Foobar extends Cl<> { }',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'new keyword' => [
            '<?php new <>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Alphabet',
                    'short_description' => 'Test\Name\Alphabet',
                    'class_import' => 'Test\Name\Alphabet',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'short_description' => 'Test\Name\Backwards',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'WithoutNS',
                    'short_description' => 'WithoutNS',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'new keyword with already imported class' => [
            '<?php use Test\Name\Alphabet; new <>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Alphabet',
                    'short_description' => 'Test\Name\Alphabet',
                    'class_import' => null,
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'short_description' => 'Test\Name\Backwards',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'WithoutNS',
                    'short_description' => 'WithoutNS',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'new keyword with namespace' => [
            '<?php namespace Test\Name; new <>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Alphabet',
                    'short_description' => 'Test\Name\Alphabet',
                    'class_import' => null,
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'short_description' => 'Test\Name\Backwards',
                    'class_import' => null,
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => null,
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'WithoutNS',
                    'short_description' => 'WithoutNS',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'new keyword with partial' => [
            '<?php new Cla<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => 'Test\Name\Clapping',
                ],
            ],
        ];

        yield 'new keyword with partial with class already imported' => [
            '<?php use Test\Name\Clapping; new Cla<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'new keyword with partial with class from same namespace' => [
            '<?php namespace Test\Name; new Cla<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'use keyword' => [
            '<?php use <>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Alphabet',
                    'short_description' => 'Test\Name\Alphabet',
                    'class_import' => 'Test\Name\Alphabet',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Backwards',
                    'short_description' => 'Test\Name\Backwards',
                    'class_import' => 'Test\Name\Backwards',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => 'Test\Name\Clapping',
                ],
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'WithoutNS',
                    'short_description' => 'WithoutNS',
                    'class_import' => null,
                ],
            ],
        ];

        yield 'use keyword with partial' => [
            '<?php use Cla<>',
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Clapping',
                    'short_description' => 'Test\Name\Clapping',
                    'class_import' => 'Test\Name\Clapping',
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
