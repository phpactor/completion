<?php

namespace Phpactor\Completion\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\Completion\Core\Suggestion;

class SuggestionsTest extends TestCase
{
    public function testReturnsAllIfStartingWithNeedleIsEmpty()
    {
        $suggestions1 = new Suggestions([
            Suggestion::create('aaa', 'v'),
        ]);
        $suggestions2 = $suggestions1->startingWith('');
        $this->assertSame($suggestions1, $suggestions2);
    }

    public function testFiltersSuggestionsStartingWith()
    {
        $suggestions = new Suggestions([
            Suggestion::create('aaa'),
            Suggestion::create('bbb'),
            Suggestion::create('aaa'),
        ]);

        $suggestions = $suggestions->startingWith('a');
        $this->assertEquals(new Suggestions([
            Suggestion::create('aaa'),
            Suggestion::create('aaa'),
        ]), $suggestions);
    }

    public function testSortSuggestions()
    {
        $suggestions = new Suggestions([
            Suggestion::create('aaa'),
            Suggestion::create('cc'),
            Suggestion::create('bbb'),
        ]);

        $suggestions = $suggestions->sorted();
        $this->assertEquals(new Suggestions([
            Suggestion::create('aaa'),
            Suggestion::create('bbb'),
            Suggestion::create('cc'),
        ]), $suggestions);
    }
}
