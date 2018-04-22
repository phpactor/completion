<?php

namespace Phpactor\Completion\Tests\Unit\Adapter\WorseReflection\Formatter;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\Completion\Adapter\WorseReflection\Formatter\ObjectFormatter;

class WorseTypeFormatterTest extends TestCase
{
    /**
     * @dataProvider provideFormat
     */
    public function testFormat(Types $types, string $expected)
    {
        $formatter = new ObjectFormatter();
        $this->assertEquals($expected, $formatter->formatTypes($types));
    }

    public function provideFormat()
    {
        yield 'no types' => [
            Types::empty(),
            '<unknown>',
        ];

        yield 'single scalar' => [
            Types::fromTypes([Type::string()]),
            'string',
        ];

        yield 'union' => [
            Types::fromTypes([Type::string(), Type::null()]),
            'string|null',
        ];

        yield 'typed array' => [
            Types::fromTypes([Type::array('string')]),
            'string[]',
        ];

        yield 'generic' => [
            Types::fromTypes([Type::collection('Collection', 'Item')]),
            'Collection<Item>',
        ];

        yield 'generic' => [
            Types::fromTypes([Type::collection('Collection', 'Item')]),
            'Collection<Item>',
        ];
    }
}
