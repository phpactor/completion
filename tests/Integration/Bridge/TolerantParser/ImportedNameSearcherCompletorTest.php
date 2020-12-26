<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser;

use Phpactor\Completion\Bridge\TolerantParser\ImportedNameSearcherCompletor;
use Phpactor\Completion\Core\Completor\NameSearcherCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\TestCase;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentBuilder;

class ImportedNameSearcherCompletorTest extends TestCase
{
    /**
     * @dataProvider provideSuggestionsToResolve
     * @dataProvider provideAnnotationToResolve
     *
     * @param Suggestion[] $expectedSuggestions
     */
    public function testResolveClassAlias(
        TextDocument $textDocument,
        ByteOffset $byteOffset,
        Suggestion $completorSuggestion,
        array $expectedSuggestions
    ): void {
        $nameSearcherCompletor = $this->prophesize(NameSearcherCompletor::class);
        $completor = new ImportedNameSearcherCompletor(
            $nameSearcherCompletor->reveal(),
        );

        $nameSearcherCompletor->complete($textDocument, $byteOffset, 'something')
            ->will(function () use ($completorSuggestion) {
                yield $completorSuggestion;

                return true;
            })
        ;

        $generator = $completor->complete($textDocument, $byteOffset, 'something');
        $suggestions = iterator_to_array($generator, false);

        $this->assertCount(count($expectedSuggestions), $suggestions);
        $this->assertTrue($generator->getReturn());
        $this->assertEqualsCanonicalizing($expectedSuggestions, $suggestions);
    }

    public function provideSuggestionsToResolve(): iterable
    {
        $textDocumentFactory = function (array $data = []): TextDocument {
            $useStatements = [];
            foreach ($data as $alias => $fqcn) {
                $alias = is_int($alias) ? null : $alias;
                $useStatements[] = "use $fqcn".($alias ? " as $alias" : '');
            }

            $useStatements = implode(PHP_EOL, $useStatements);

            return TextDocumentBuilder::create(
                <<<EOT
<?php

namespace Test;

$useStatements

class BarTest
{
    public function methodName(Bar \$bar)
    {
    }
}
EOT
            )->build();
        };
        $computeByteOffset = function (TextDocument $textDocument): ByteOffset {
            $matches = [];
            preg_match('/Bar \$bar/u', (string) $textDocument, $matches, PREG_OFFSET_CAPTURE);

            return ByteOffset::fromInt($matches[0][1] + 2);
        };
        $suggestion = Suggestion::createWithOptions('Bar', [
            'short_description' => 'App\Foo\Bar',
            'type' => Suggestion::TYPE_CLASS,
            'name_import' => 'App\Foo\Bar',
        ]);

        yield 'Class not imported yet' => [
            $textDocument = $textDocumentFactory(),
            $computeByteOffset($textDocument),
            $suggestion,
            [],
        ];

        yield 'Class imported without an alias' => [
            $textDocument = $textDocumentFactory(['App\Foo\Bar']),
            $computeByteOffset($textDocument),
            $suggestion,
            [],
        ];

        yield 'Class imported with an alias' => [
            $textDocument = $textDocumentFactory(['Foobar' => 'App\Foo\Bar']),
            $computeByteOffset($textDocument),
            $suggestion,
            [$suggestion->withoutNameImport()->withName('Foobar')],
        ];

        yield 'Class imported with and without an alias' => [
            $textDocument = $textDocumentFactory([
                'Foobar' => 'App\Foo\Bar',
                'App\Foo\Bar',
            ]),
            $computeByteOffset($textDocument),
            $suggestion,
            [$suggestion->withoutNameImport()->withName('Foobar')],
        ];

        yield 'Class imported with an aliased namespace' => [
            $textDocument = $textDocumentFactory([
                'FOO' => 'App\Foo',
                'APP' => 'App',
            ]),
            $computeByteOffset($textDocument),
            $suggestion,
            [
                $suggestion->withoutNameImport()->withName('FOO\Bar'),
                $suggestion->withoutNameImport()->withName('APP\Foo\Bar'),
            ],
        ];

        yield 'Class imported with and without an aliased namespace' => [
            $textDocument = $textDocumentFactory([
                'App\Foo\Bar',
                'FOO' => 'App\Foo',
            ]),
            $computeByteOffset($textDocument),
            $suggestion,
            [$suggestion->withoutNameImport()->withName('FOO\Bar')],
        ];
    }

    public function provideAnnotationToResolve(): iterable
    {
        $textDocumentFactory = function (array $data = []): TextDocument {
            $useStatements = [];
            foreach ($data as $alias => $fqcn) {
                $alias = is_int($alias) ? null : $alias;
                $useStatements[] = "use $fqcn".($alias ? " as $alias" : '');
            }

            $useStatements = implode(PHP_EOL, $useStatements);

            return TextDocumentBuilder::create(
                <<<EOT
<?php

namespace Test;

$useStatements

/**
 * @Ba
 */
class BarTest
{
    private \$test;
}
EOT
            )->build();
        };
        $computeByteOffset = function (TextDocument $textDocument): ByteOffset {
            $matches = [];
            preg_match('/\* @Ba/u', (string) $textDocument, $matches, PREG_OFFSET_CAPTURE);

            return ByteOffset::fromInt($matches[0][1] + 5);
        };
        $suggestion = Suggestion::createWithOptions('Bar', [
            'short_description' => 'App\Foo\Bar',
            'type' => Suggestion::TYPE_CLASS,
            'name_import' => 'App\Foo\Bar',
            'snippet' => 'Bar($1)$0',
        ]);

        yield 'Annotation not imported yet' => [
            $textDocument = $textDocumentFactory(),
            $computeByteOffset($textDocument),
            $suggestion,
            [],
        ];

        yield 'Annotation imported without an alias' => [
            $textDocument = $textDocumentFactory(['App\Foo\Bar']),
            $computeByteOffset($textDocument),
            $suggestion,
            [],
        ];

        yield 'Annotation imported with an alias' => [
            $textDocument = $textDocumentFactory(['Foobar' => 'App\Foo\Bar']),
            $computeByteOffset($textDocument),
            $suggestion,
            [$suggestion->withoutNameImport()->withName('Foobar')->withSnippet('Foobar($1)$0')],
        ];

        yield 'Annotation imported with and without an alias' => [
            $textDocument = $textDocumentFactory([
                'Foobar' => 'App\Foo\Bar',
                'App\Foo\Bar',
            ]),
            $computeByteOffset($textDocument),
            $suggestion,
            [$suggestion->withoutNameImport()->withName('Foobar')->withSnippet('Foobar($1)$0')],
        ];

        yield 'Annotation imported with an aliased namespace' => [
            $textDocument = $textDocumentFactory([
                'FOO' => 'App\Foo',
                'APP' => 'App',
            ]),
            $computeByteOffset($textDocument),
            $suggestion,
            [
                $suggestion->withoutNameImport()->withName('FOO\Bar')->withSnippet('FOO\Bar($1)$0'),
                $suggestion->withoutNameImport()->withName('APP\Foo\Bar')->withSnippet('APP\Foo\Bar($1)$0'),
            ],
        ];

        yield 'Annotation imported with and without an aliased namespace' => [
            $textDocument = $textDocumentFactory([
                'App\Foo\Bar',
                'FOO' => 'App\Foo',
            ]),
            $computeByteOffset($textDocument),
            $suggestion,
            [$suggestion->withoutNameImport()->withName('FOO\Bar')->withSnippet('FOO\Bar($1)$0')],
        ];
    }
}
