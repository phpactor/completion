<?php

namespace Phpactor\Completion\Tests\Integration\Extension\Util;

use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Extension\Util\SuggestionNameFormatter;
use PHPUnit\Framework\TestCase;

class SuggestionNameFormatterTest extends TestCase
{
    /**
     * @var SuggestionNameFormatter
     */
    private $formatter;

    protected function setUp(): void
    {
        $this->formatter = new SuggestionNameFormatter(true);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testFormat(string $type, string $name, string $expected)
    {
        $suggestion = Suggestion::createWithOptions($name, ['type' => $type]);

        $this->assertSame($expected, $this->formatter->format($suggestion));
    }

    public function dataProvider(): array
    {
        return [
            [Suggestion::TYPE_VARIABLE, '$foo', 'foo'],
            [Suggestion::TYPE_FUNCTION, 'foo', 'foo('],
            [Suggestion::TYPE_FIELD, 'foo', 'foo'],
        ];
    }
}
