<?php

namespace Phpactor\Completion\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Suggestion;
use RuntimeException;

class SuggestionTest extends TestCase
{
    public function testThrowsExceptionWithInvalidOptions()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid options for suggestion: "foobar" valid options: "short_description", "type"');

        Suggestion::createWithOptions('foobar', ['foobar' => 'barfoo']);
    }

    public function testCanBeCreatedWithOptions()
    {
        $suggestion = Suggestion::createWithOptions('hello', [
            'type' => 'm',
            'short_description' => 'foobar'
        ]);

        $this->assertEquals('m', $suggestion->type());
        $this->assertEquals('hello', $suggestion->name());
        $this->assertEquals('foobar', $suggestion->info());
    }
}
