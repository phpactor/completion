<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseSignatureHelper;
use Phpactor\Completion\Core\Exception\CouldNotHelpWithSignature;
use Phpactor\Completion\Core\ParameterInformation;
use Phpactor\Completion\Core\SignatureHelp;
use Phpactor\Completion\Core\SignatureInformation;
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

        yield 'function with parameters, 2nd active' => [
            '<?php function hello(string $foo, int $bar) {}; hello("hello",<>',
            new SignatureHelp(
                [new SignatureInformation(
                    'hello(string $foo, int $bar)',
                    [
                        new ParameterInformation('foo', 'string $foo'),
                        new ParameterInformation('bar', 'int $bar'),
                    ]
                )],
                1,
                null
            )
        ];

        yield 'function with parameters, 2nd active 1' => [
            '<?php function hello(string $foo, int $bar) {}; hello("hello",<>',
            new SignatureHelp(
                [new SignatureInformation(
                    'hello(string $foo, int $bar)',
                    [
                        new ParameterInformation('foo', 'string $foo'),
                        new ParameterInformation('bar', 'int $bar'),
                    ]
                )],
                1,
                null
            )
        ];

        yield 'static method call' => [
            '<?php class Foo { static function hello(string $foo, int $bar) {} }; Foo::hello(<>',
            new SignatureHelp(
                [new SignatureInformation(
                    'pub hello(string $foo, int $bar)',
                    [
                        new ParameterInformation('foo', 'string $foo'),
                        new ParameterInformation('bar', 'int $bar'),
                    ]
                )],
                0,
                null
            )
        ];

        yield 'static method call, 2nd active' => [
            '<?php class Foo { static function hello(string $foo, int $bar) {} }; Foo::hello("hello",<>',
            new SignatureHelp(
                [new SignatureInformation(
                    'pub hello(string $foo, int $bar)',
                    [
                        new ParameterInformation('foo', 'string $foo'),
                        new ParameterInformation('bar', 'int $bar'),
                    ]
                )],
                1,
                null
            )
        ];

        yield 'static method call, on variable' => [
            '<?php $foo = "Foo"; $foo::hello("hello",<>',
            null
        ];

        yield 'instance method' => [
            '<?php class Foo { function hello(string $foo, int $bar) {} }; $foo = new Foo(); $foo->hello(<>',
            new SignatureHelp(
                [new SignatureInformation(
                    'pub hello(string $foo, int $bar)',
                    [
                        new ParameterInformation('foo', 'string $foo'),
                        new ParameterInformation('bar', 'int $bar'),
                    ]
                )],
                0,
                null
            )
        ];

        yield 'instance from an interface' => [
            '<?php interface Foo { function hello(string $foo, int $bar): void }; function (Foo $foo) { $foo->hello(<>',
            new SignatureHelp(
                [new SignatureInformation(
                    'pub hello(string $foo, int $bar): void',
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
