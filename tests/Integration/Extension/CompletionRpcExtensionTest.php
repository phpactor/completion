<?php

namespace Phpactor\Completion\Tests\Integration\Extension;

use Phpactor\Completion\Tests\TestCase;
use Phpactor\Container\PhpactorContainer;
use Phpactor\Extension\Logger\LoggingExtension;
use Phpactor\Completion\Extension\CompletionRpcExtension;
use Phpactor\Completion\Extension\CompletionExtension;
use Phpactor\Extension\Rpc\Request;
use Phpactor\Extension\Rpc\RequestHandler;
use Phpactor\Extension\Rpc\Response\ReturnResponse;
use Phpactor\Extension\Rpc\RpcExtension;

class CompletionRpcExtensionTest extends TestCase
{
    public function testAddsCompletionHandler()
    {
        $handler = $this->createRequestHandler();
        $response = $handler->handle(Request::fromNameAndParameters('complete', [
            'source' => '',
            'offset' => 1,
        ]));
        $this->assertInstanceOf(ReturnResponse::class, $response);
    }

    private function createRequestHandler(): RequestHandler
    {
        $container = PhpactorContainer::fromExtensions([
            CompletionRpcExtension::class,
            RpcExtension::class,
            CompletionExtension::class,
            LoggingExtension::class,
        ]);
        
        return $container->get(RpcExtension::SERVICE_REQUEST_HANDLER);
    }
}
