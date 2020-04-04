<?php

namespace Phpactor\Completion\Tests\Integration\Extension\LanguageServer;

use Generator;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionList;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\TextDocumentItem;
use LanguageServerProtocol\TextEdit;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Range as PhpactorRange;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\TypedCompletor;
use Phpactor\Completion\Core\TypedCompletorRegistry;
use Phpactor\Completion\Extension\LanguageServer\CompletionHandler;
use Phpactor\Completion\Extension\Util\SuggestionNameFormatter;
use Phpactor\LanguageServer\Core\Rpc\ResponseMessage;
use Phpactor\LanguageServer\Core\Session\Workspace;
use Phpactor\LanguageServer\Test\HandlerTester;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;

class CompletionHandlerTest extends TestCase
{
    /**
     * @var TextDocumentItem
     */
    private $document;

    /**
     * @var Position
     */
    private $position;


    public function setUp(): void
    {
        $this->document = new TextDocumentItem();
        $this->document->uri = '/test';
        $this->document->text = 'hello';
        $this->position = new Position(1, 1);
        $this->workspace = new Workspace();

        $this->workspace->open($this->document);
    }

    public function testHandleNoSuggestions()
    {
        $tester = $this->create([]);
        $responses = $tester->dispatch(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $this->assertInstanceOf(ResponseMessage::class, $responses[0]);
        $list = $responses[0]->result;
        $this->assertInstanceOf(CompletionList::class, $list);
        $this->assertEquals([], $list->items);
    }

    public function testHandleSuggestions()
    {
        $tester = $this->create([
            Suggestion::create('hello'),
            Suggestion::create('goodbye'),
        ]);
        $responses = $tester->dispatch(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $this->assertInstanceOf(ResponseMessage::class, $responses[0]);
        $list = $responses[0]->result;
        $this->assertInstanceOf(CompletionList::class, $list);
        $this->assertEquals([
            new CompletionItem('hello'),
            new CompletionItem('goodbye'),
        ], $list->items);
    }

    public function testHandleSuggestionsWithRange()
    {
        $tester = $this->create([
            Suggestion::createWithOptions('hello', [ 'range' => PhpactorRange::fromStartAndEnd(1, 2)]),
        ]);
        $responses = $tester->dispatch(
            'textDocument/completion',
            [
                'textDocument' => $this->document,
                'position' => $this->position
            ]
        );
        $this->assertInstanceOf(ResponseMessage::class, $responses[0]);
        $list = $responses[0]->result;
        $this->assertInstanceOf(CompletionList::class, $list);
        $this->assertEquals([
            new CompletionItem('hello', null, '', null, null, null, null, new TextEdit(
                new Range(new Position(0, 1), new Position(0, 2)),
                'hello'
            )),
        ], $list->items);
    }

    private function create(array $suggestions): HandlerTester
    {
        $completor = $this->createCompletor($suggestions);
        $registry = new TypedCompletorRegistry([
            new TypedCompletor($completor, [ 'php' ])
        ]);
        return new HandlerTester(new CompletionHandler(
            $this->workspace,
            $registry,
            new SuggestionNameFormatter(),
            true
        ));
    }

    private function createCompletor(array $suggestions)
    {
        return new class($suggestions) implements Completor {
            private $suggestions;
            public function __construct(array $suggestions)
            {
                $this->suggestions = $suggestions;
            }

            public function complete(TextDocument $source, ByteOffset $offset): Generator
            {
                foreach ($this->suggestions as $suggestion) {
                    yield $suggestion;
                }
            }
        };
    }
}
