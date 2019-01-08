<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseSignatureHelper;
use Phpactor\Completion\Core\Exception\CouldNotHelpWithSignature;
use Phpactor\Completion\Core\ParameterInformation;
use Phpactor\Completion\Core\SignatureHelp;
use Phpactor\Completion\Core\SignatureInformation;
use Phpactor\Completion\Tests\Integration\CompletorTestCase;
use Phpactor\Completion\Tests\Integration\IntegrationTestCase;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\WorseReflection\ReflectorBuilder;

class WorseSignatureHelperTest extends IntegrationTestCase
{
    /**
     * @dataProvider provideSignatureHelper
     */
    public function testSignatureHelper(string $source, ?SignatureHelp $expected)
    {
        if ($expected === null) {
            $this->expectException(CouldNotHelpWithSignature::class);
        }

        [ $source, $offset ] = ExtractOffset::fromSource($source);
        $reflector = ReflectorBuilder::create()->addSource($source)->build();

        $helper = new WorseSignatureHelper($reflector, $this->formatter());

        $help = $helper->signatureHelp(
            TextDocumentBuilder::create($source)->language('php')->build(),
            ByteOffset::fromInt($offset)
        );

        $this->assertEquals($expected, $help);
    }

    public function provideSignatureHelper()
    {
        yield 'not a signature' => [
            '<?php echo "h<>ello";',
            null
        ];

        yield 'function signature with no parameters' => [
            '<?php function hello() {}; hello(<>',
            new SignatureHelp(
                [new SignatureInformation(
                    'hello()',
                    []
                )],
                0,
                null
            )
        ];

        yield 'function with parameter' => [
            '<?php function hello(string $foo) {}; hello(<>',
            new SignatureHelp(
                [new SignatureInformation(
                    'hello(string $foo)',
                    [
                        new ParameterInformation('foo', 'string $foo'),
                    ]
                )],
                0,
                null
            )
        ];

        yield 'function with parameters' => [
            '<?php function hello(string $foo, int $bar) {}; hello(<>',
            new SignatureHelp(
                [new SignatureInformation(
                    'hello(string $foo, int $bar)',
                    [
                        new ParameterInformation('foo', 'string $foo'),
                        new ParameterInformation('bar', 'int $bar'),
                    ]
                )],
                0,
                null
            )
        ];
    }
}
