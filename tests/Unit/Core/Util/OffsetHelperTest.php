<?php

namespace Phpactor\Completion\Tests\Unit\Core\Util;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Util\OffsetHelper;
use Phpactor\TestUtils\ExtractOffset;

class OffsetHelperTest extends TestCase
{
    /**
     * @dataProvider provideReturnsLastNonWhitespaceOffset
     */
    public function testReturnsLastNonWhitespaceOffset(string $example)
    {
        list($source, $expectedOffset) = ExtractOffset::fromSource($example);
        $offset = OffsetHelper::lastNonWhitespaceOffset($source);

        $this->assertEquals($expectedOffset, $offset);
    }

    public function provideReturnsLastNonWhitespaceOffset()
    {
        yield 'empty string' => [
            '',
            0
        ];

        yield 'no extra whitespace' => [
            'foobar<>',
            6
        ];

        yield 'extra whitespace' => [
            'foobar<>    ',
            6
        ];

        yield 'extra newline' => [
            'foobar<>' . PHP_EOL,
            6
        ];

        yield 'extra windows newline' => [
            "foobar<>\r\n",
            6
        ];

        yield 'extra tab' => [
            "foobar<>\t",
            6
        ];
    }
}
