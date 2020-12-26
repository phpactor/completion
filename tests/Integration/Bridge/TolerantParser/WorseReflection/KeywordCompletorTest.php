<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use Microsoft\PhpParser\TokenStringMaps;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\KeywordCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\TextDocument\TextDocument;

class KeywordCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(TextDocument $source): TolerantCompletor
    {
        return new KeywordCompletor(
        );
    }
    
    public function testComplete(): void
    {
        $expected = [];
        $keywords = array_merge(array_keys(TokenStringMaps::RESERVED_WORDS), array_keys(TokenStringMaps::KEYWORDS));
        sort($keywords);
        foreach ($keywords as $keyword) {
            $expected[] = [
                'type' => Suggestion::TYPE_KEYWORD,
                'name' => $keyword,
            ];
        }

        $this->assertComplete("<?php <>", $expected);
    }
}
