<?php

namespace Phpactor\Completion\Tests\Unit\Bridge\TolerantParser;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Parser;
use Phpactor\Completion\Bridge\TolerantParser\ChainTolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantQualifiable;
use Phpactor\Completion\Bridge\TolerantParser\TolerantQualifier;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\TestCase;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentBuilder;
use Prophecy\Argument;

class ChainTolerantCompletorTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $completor1;
    /**
     * @var ObjectProphecy
     */
    private $qualifiableCompletor1;
    /**
     * @var ObjectProphecy
     */
    private $qualifier1;

    /**
     * @var ObjectProphecy
     */
    private $qualifiableCompletor2;

    /**
     * @var ObjectProphecy
     */
    private $qualifier2;

    protected function setUp(): void
    {
        $this->completor1 = $this->prophesize(TolerantCompletor::class);
        $this->qualifiableCompletor1 = $this->prophesize(TolerantCompletor::class)
            ->willImplement(TolerantQualifiable::class);
        $this->qualifiableCompletor2 = $this->prophesize(TolerantCompletor::class)
            ->willImplement(TolerantQualifiable::class);

        $this->qualifier1 = $this->prophesize(TolerantQualifier::class);
        $this->qualifier2 = $this->prophesize(TolerantQualifier::class);
    }

    public function testEmptyResponseWithNoCompletors()
    {
        $completor = $this->create([]);
        $suggestions = $completor->complete(
            TextDocumentBuilder::create('<?php ')->build(),
            ByteOffset::fromInt(1)
        );
        $this->assertCount(0, $suggestions);
        $this->assertTrue($suggestions->getReturn());
    }

    public function testCallsCompletors()
    {
        $completor = $this->create([
            $this->completor1->reveal(),
        ]);

        $this->completor1->complete(
            Argument::type(Node::class),
            TextDocumentBuilder::create('<?php ')->build(),
            ByteOffset::fromInt(1)
        )->will(function () {
            yield Suggestion::create('foo');
            return false;
        });

        $suggestions = $completor->complete(
            TextDocumentBuilder::create('<?php ')->build(),
            ByteOffset::fromInt(1)
        );
        $this->assertCount(1, $suggestions);
        $this->assertFalse($suggestions->getReturn());
    }

    public function testPassesCorrectByteOffsetToParser()
    {
        $completor = $this->create([ $this->completor1->reveal() ]);
        list($source, $offset) = ExtractOffset::fromSource(
            <<<'EOT'
<?php

// 姓名

class A
{
  public function foo()
  {
  }
}

$a = new A;
$<>
EOT
        );

        // the parser node passed to the tolerant completor should be the one
        // at the requested char offset
        $this->completor1->complete(
            Argument::that(function ($arg) {
                return $arg->getText() === '$';
            }),
            $source,
            $offset
        )->will(function ($args) {
            return;
        });

        $completor->complete(
            TextDocumentBuilder::create($source)->build(),
            ByteOffset::fromInt($offset)
        );
        $this->addToAssertionCount(1);
    }

    public function testExcludesNonQualifingClasses()
    {
        $completor = $this->create([
            $this->qualifiableCompletor1->reveal(),
            $this->qualifiableCompletor2->reveal(),
        ]);
        $this->qualifiableCompletor1->qualifier()->willReturn($this->qualifier1->reveal());
        $this->qualifiableCompletor2->qualifier()->willReturn($this->qualifier2->reveal());

        $this->qualifier1->couldComplete(Argument::type(Node::class))->shouldBeCalled()->will(function (array $args) {
            return $args[0];
        });
        $this->qualifier2->couldComplete(Argument::type(Node::class))->shouldBeCalled()->willReturn(null);

        $this->qualifiableCompletor1->complete(
            Argument::type(Node::class),
            TextDocumentBuilder::create('<?php ')->build(),
            ByteOffset::fromInt(1)
        )->will(function () {
            yield Suggestion::create('foo');
            return true;
        });
        $this->qualifiableCompletor2->complete(Argument::cetera())->shouldNotBeCalled();

        $suggestions = $completor->complete(
            TextDocumentBuilder::create('<?php ')->build(),
            ByteOffset::fromInt(1)
        );
        $this->assertCount(1, $suggestions);
        $this->assertTrue($suggestions->getReturn());
    }

    /**
     * @dataProvider provideSuggestionsToResolve
     */
    public function testResolveImportName(
        TextDocument $textDocument,
        ByteOffset $byteOffset,
        Suggestion $completorSuggestion,
        Suggestion $expectedSuggestion
    ): void {
        $completor = $this->create([$this->completor1->reveal()]);

        $this->completor1->complete(Argument::type(Node::class), $textDocument, $byteOffset)
            ->will(function () use ($completorSuggestion) {
                yield $completorSuggestion;

                return false;
            })
        ;

        $generator = $completor->complete($textDocument, $byteOffset);
        $suggestions = iterator_to_array($generator);

        $this->assertCount(1, $suggestions);
        $this->assertFalse($generator->getReturn());
        $this->assertEquals($expectedSuggestion, $suggestions[0]);
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

        yield 'Not imported yet' => [
            $textDocument = $textDocumentFactory(),
            $computeByteOffset($textDocument),
            $suggestion,
            $suggestion,
        ];

        yield 'Imported without an alias' => [
            $textDocument = $textDocumentFactory(['App\Foo\Bar']),
            $computeByteOffset($textDocument),
            $suggestion,
            $suggestion->withoutNameImport(),
        ];

        yield 'Imported with an alias' => [
            $textDocument = $textDocumentFactory(['Foobar' => 'App\Foo\Bar']),
            $computeByteOffset($textDocument),
            $suggestion,
            $suggestion->withoutNameImport()->withName('Foobar'),
        ];

        yield 'Imported with and without an alias' => [
            $textDocument = $textDocumentFactory([
                'Foobar' => 'App\Foo\Bar',
                'App\Foo\Bar',
            ]),
            $computeByteOffset($textDocument),
            $suggestion,
            $suggestion->withoutNameImport(),
        ];

        yield 'Imported with an aliased namespace' => [
            $textDocument = $textDocumentFactory([
                'FOO' => 'App\Foo',
                'APP' => 'App',
            ]),
            $computeByteOffset($textDocument),
            $suggestion,
            $suggestion->withoutNameImport()->withName('FOO\Bar'),
        ];

        yield 'Imported with and without an aliased namespace' => [
            $textDocument = $textDocumentFactory([
                'App\Foo\Bar',
                'FOO' => 'App\Foo',
            ]),
            $computeByteOffset($textDocument),
            $suggestion,
            $suggestion->withoutNameImport(),
        ];
    }

    private function create(array $completors): ChainTolerantCompletor
    {
        return new ChainTolerantCompletor($completors);
    }
}
