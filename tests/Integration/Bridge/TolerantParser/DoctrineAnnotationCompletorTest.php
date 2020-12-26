<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser;

use Generator;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\DoctrineAnnotationCompletor;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\CompletorTestCase;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\WorseReflection\Bridge\Phpactor\MemberProvider\DocblockMemberProvider;
use Phpactor\WorseReflection\ReflectorBuilder;
use Prophecy\Argument;

class DoctrineAnnotationCompletorTest extends CompletorTestCase
{
    protected function createCompletor(string $source): Completor
    {
        $source = TextDocumentBuilder::create($source)->uri('file:///tmp/test')->build();

        $nameSearcherCompletor = $this->prophesize(NameSearcherCompletor::class);
        $nameSearcherCompletor->complete(Argument::any())->willYield([]);
        $nameSearcherCompletor->complete(Argument::type(TextDocument::class), Argument::type(ByteOffset::class), 'Ann')->will(function () {
            yield Suggestion::createWithOptions('Annotation', [
                'type' => Suggestion::TYPE_CLASS,
                'short_description' => 'Annotation',
                'name_import' => 'Annotation',
            ]);

            return true;
        });
        $nameSearcherCompletor->complete(Argument::type(TextDocument::class), Argument::type(ByteOffset::class), 'Ent')->will(function () {
            yield Suggestion::createWithOptions('Entity', [
                'type' => Suggestion::TYPE_CLASS,
                'short_description' => 'App\Annotation\Entity',
                'name_import' => 'App\Annotation\Entity',
            ]);

            return true;
        });
        $nameSearcherCompletor->complete(Argument::type(TextDocument::class), Argument::type(ByteOffset::class), 'NotAnn')->will(function () {
            yield Suggestion::createWithOptions('NotAnnotation', [
                'type' => Suggestion::TYPE_CLASS,
                'short_description' => 'NotAnnotation',
                'name_import' => 'NotAnnotation',
            ]);

            return true;
        });

        $reflector = ReflectorBuilder::create()
            ->addMemberProvider(new DocblockMemberProvider())
            ->addSource($source)->build();

        return new DoctrineAnnotationCompletor(
            $nameSearcherCompletor->reveal(),
            $reflector,
        );
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
        yield 'not a docblock' => [
            <<<'EOT'
<?php

/**
 * @Annotation
 */
class Annotation {}

/*
 * @Ann<>
 */
class Foo {}
EOT
            , []
        ];

        yield 'not a text annotation' => [
            <<<'EOT'
<?php

/**
 * Ann<>
 */
class Foo {}
EOT
            , []
        ];

        yield 'in a namespace' => [
            <<<'EOT'
<?php

namespace App\Annotation;

/**
 * @Annotation
 */
class Entity {}

namespace App;

/**
 * @Ent<>
 */
class Foo {}
EOT
        , [
            [
                'type' => Suggestion::TYPE_CLASS,
                'name' => 'Entity',
                'short_description' => 'App\Annotation\Entity',
                'snippet' => 'Entity($1)$0',
                'name_import' => 'App\Annotation\Entity',
            ]
        ]];

        yield 'annotation on a node in the middle of the AST' => [
            <<<'EOT'
<?php

/**
 * @Annotation
 */
class Annotation {}

class Foo
{
    /**
     * @var string
     */
    private $foo;

    /**
     * @Ann<>
     */
    public function foo(): string
    {
        return 'foo';
    }

    public function bar(): string
    {
        return 'bar';
    }
}
EOT
        , [
            [
                'type' => Suggestion::TYPE_CLASS,
                'name' => 'Annotation',
                'short_description' => 'Annotation',
                'snippet' => 'Annotation($1)$0'
            ]
        ]];

        yield 'not an annotation class' => [
            <<<'EOT'
<?php

class NotAnnotation {}

/**
 * @NotAnn<>
 */
class Foo {}
EOT
            , []
        ];

        yield 'handle errors if class not found' => [
            <<<'EOT'
<?php

/**
 * @NotAnn<>
 */
class Foo {}
EOT
            , []
        ];
    }
}
