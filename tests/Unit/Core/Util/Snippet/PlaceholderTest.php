<?php

namespace Phpactor\Completion\Tests\Unit\Core\Util\Snippet;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Util\Snippet\Placeholder;

final class PlaceholderTest extends TestCase
{
    /**
     * @dataProvider providePlaceholders
     */
    public function testRaw(int $position, ?string $text, string $expected): void
    {
        $this->assertEquals(
            \sprintf('${%d%s}', $position, $text ? ":$text" : null),
            Placeholder::raw($position, $text)
        );
    }

    /**
     * @dataProvider providePlaceholders
     */
    public function testEscape(int $position, ?string $text, string $expected): void
    {
        $this->assertEquals(
            $expected,
            Placeholder::escape($position, $text)
        );
    }

    public function providePlaceholders(): iterable
    {
        yield 'No text' => [1, null, '${1}'];
        yield 'With text' => [3, 'default', '${3:default}'];
        yield 'With a $' => [3, '$default', '${3:\$default}'];
        yield 'With a \\' => [3, '\default', '${3:\\\default}'];
        yield 'With a }' => [3, 'default}', '${3:default\}}'];
    }
}
