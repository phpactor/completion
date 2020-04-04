<?php

namespace Phpactor\Completion\Tests\Integration\Extension\Rpc;

use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\TypedCompletor;
use Phpactor\Completion\Core\TypedCompletorRegistry;
use Phpactor\Completion\Extension\Rpc\CompleteHandler;
use Phpactor\Completion\Tests\TestCase;
use Phpactor\Extension\Rpc\Response\ReturnResponse;
use Phpactor\Extension\Rpc\Test\HandlerTester;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use Prophecy\Prophecy\ObjectProphecy;

class CompleteHandlerTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $completor;

    protected function setUp(): void
    {
        $this->completor = $this->prophesize(Completor::class);
        $this->registry = new TypedCompletorRegistry([
            new TypedCompletor($this->completor->reveal(), ['php'])
        ]);
    }

    public function testHandler()
    {
        $handler = new CompleteHandler($this->registry);
        $this->completor->complete(
            TextDocumentBuilder::create('aaa')->language('php')->build(),
            ByteOffset::fromInt(1234)
        )->will(function () {
            yield Suggestion::create('aaa');
            yield Suggestion::create('bbb');
        });
        $action = (new HandlerTester($handler))->handle('complete', [
            'source' => 'aaa',
            'offset' => 1234
        ]);

        $this->assertInstanceOf(ReturnResponse::class, $action);
        $this->assertArraySubset([
            [
                'name' => 'aaa',
            ],
            [
                'name' => 'bbb',
            ],
        ], $action->value()['suggestions']);
    }
}
