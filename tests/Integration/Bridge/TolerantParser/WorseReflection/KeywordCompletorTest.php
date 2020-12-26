<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use Generator;
use Microsoft\PhpParser\TokenStringMaps;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\KeywordCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\TextDocument\TextDocument;
use function func_get_arg;

class KeywordCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(TextDocument $source): TolerantCompletor
    {
        return new KeywordCompletor();
    }
    /**
	 * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected): void
    {
        $this->assertComplete($source, $expected);
	}
	
	public function provideComplete(): Generator
	{
		$allKeywords = [];
        $keywords = array_merge(array_keys(TokenStringMaps::RESERVED_WORDS), array_keys(TokenStringMaps::KEYWORDS));
        sort($keywords);
        foreach ($keywords as $keyword) {
            $allKeywords[] = [
                'type' => Suggestion::TYPE_KEYWORD,
                'name' => $keyword,
            ];
		}
		
		yield 'all keywords' => [
			'<?php <>',
			$allKeywords
        ];
        
        yield 'member access' => [
			'<?php function F(){ $v-><>',
			[]
		];
		
		return true;
	}
}
