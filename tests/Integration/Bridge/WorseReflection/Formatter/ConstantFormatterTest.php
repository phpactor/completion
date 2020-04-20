<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\WorseReflection\Formatter;

use Phpactor\Completion\Tests\Integration\IntegrationTestCase;
use Phpactor\WorseReflection\ReflectorBuilder;

class ConstantFormatterTest extends IntegrationTestCase
{
    /**
     * @dataProvider provideFormatConstant
     */
    public function testFormatsConstant(string $code, string $expected)
    {
        $constant = ReflectorBuilder::create()->build()->reflectClassesIn(
            $code
        )->first()->constants()->first();

        self::assertTrue($this->formatter()->canFormat($constant));
        self::assertEquals($expected, $this->formatter()->format($constant));
    }

    public function provideFormatConstant()
    {
        yield 'string' => [
            '<?php namespace Bar {class Foobar {const BAR = "FOO";}}',
            'const BAR = "FOO"',
        ];

        yield 'int' => [
            '<?php namespace Bar {class Foobar {const BAR = 123;}}',
            'const BAR = 123',
        ];

        yield 'invalid' => [
            '<?php namespace Bar {class Foobar {const BAR}}',
            'const BAR = null',
        ];

        yield 'array' => [
            '<?php namespace Bar {class Foobar {const BAR=[123]}}',
            'const BAR = [123]',
        ];
    }
}
