<?php

namespace Phpactor\Completion\Tests\Unit\Bridge\TolerantParser;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\ChainTolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;
use Prophecy\Argument;

class ChainTolerantCompletorTest extends TestCase
{
    public function setUp()
    {
        $this->completor1 = $this->prophesize(TolerantCompletor::class);
    }

    public function testEmptyResponseWithNoCompletors()
    {
        $completor = $this->create([]);
        $result = $completor->complete('<?php ', 1);
        $this->assertCount(0, $result->suggestions());
    }

    public function testCallsCompletors()
    {
        $completor = $this->create([
            $this->completor1->reveal(),
        ]);

        $this->completor1->complete(
            Argument::type(Node::class),
            '<?php ',
            1
        )->willReturn(
            Response::fromSuggestions(
                Suggestions::fromSuggestions([
                    Suggestion::create('v', 'foo', 'bar')
                ])
            )
        );

        $result = $completor->complete('<?php ', 1);
        $this->assertCount(1, $result->suggestions());
    }

    private function create(array $completors): ChainTolerantCompletor
    {
        return new ChainTolerantCompletor($completors);
    }
}
