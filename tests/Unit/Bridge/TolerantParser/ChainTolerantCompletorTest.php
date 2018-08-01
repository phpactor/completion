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
use Phpactor\Completion\Core\Util\OffsetHelper;
use Phpactor\TestUtils\ExtractOffset;
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

    public function testPassesCorrectByteOffsetToParser()
    {
        $completor = $this->create([ $this->completor1->reveal() ]);
        [ $source, $offset ] = ExtractOffset::fromSource(<<<'EOT'
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
            return Response::new();
        });
        $completor->complete($source, $offset);
    }

    private function create(array $completors): ChainTolerantCompletor
    {
        return new ChainTolerantCompletor($completors);
    }
}
