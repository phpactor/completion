<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseSignatureHelper;
use Phpactor\Completion\Core\Exception\CouldNotHelpWithSignature;
use Phpactor\Completion\Core\SignatureHelp;
use Phpactor\Completion\Core\SignatureInformation;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\WorseReflection\ReflectorBuilder;

class WorseSignatureHelperTest extends TestCase
{
    /**
     * @dataProvider provideSignatureHelper
     */
    public function testSignatureHelper(string $source, ?SignatureHelp $expected)
    {
        [ $source, $offset ] = ExtractOffset::fromSource($source);
        $reflector = ReflectorBuilder::create()->addSource($source)->build();

        $helper = new WorseSignatureHelper($reflector);

        try {
            $help = $helper->signatureHelp(
                TextDocumentBuilder::create($source)->language('php')->build(),
                ByteOffset::fromInt($offset)
            );
        } catch (CouldNotHelpWithSignature $couldNotHelp) {
            $help = null;
        }

        $this->assertEquals($expected, $help);
    }

    public function provideSignatureHelper()
    {
        yield 'not a signature' => [
            '<?php echo "h<>ello";',
            null
        ];

        yield 'function signature with no parameters' => [
            '<?php function hello() {}; hello<>(',
            new SignatureHelp(
                [new SignatureInformation(
                    'hello()',
                    []
                )],
                0,
                null
            )
        ];
    }
}
