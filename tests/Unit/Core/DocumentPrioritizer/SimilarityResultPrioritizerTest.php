<?php

namespace Phpactor\Completion\Tests\Unit\Core\DocumentPrioritizer;

use Generator;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\DocumentPrioritizer\SimilarityResultPrioritizer;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\TextDocument\TextDocumentUri;

class SimilarityResultPrioritizerTest extends TestCase
{
    /**
     * @dataProvider providePriority
     */
    public function testPriority(?TextDocumentUri $one, ?TextDocumentUri $two, int $priority): void
    {
        self::assertEquals($priority, (new SimilarityResultPrioritizer())->priority($one, $two));
    }
        
    /**
     * @return Generator<mixed>
     */
    public function providePriority(): Generator
    {
        yield [
                null,
                null,
                Suggestion::PRIORITY_LOW
            ];
        yield [
                TextDocumentUri::fromString('/home/daniel/phpactor/vendor/symfony/foobar/lib/ClassOne.php'),
                TextDocumentUri::fromString('/home/daniel/phpactor/lib/ClassOne.php'),
                169 // higher priority for non matching
            ];
        yield [
                TextDocumentUri::fromString('/home/daniel/phpactor/vendor/symfony/foobar/lib/ClassOne.php'),
                TextDocumentUri::fromString('/home/daniel/phpactor/vendor/symfony/foobar/lib/ClassOne.php'),
                Suggestion::PRIORITY_MEDIUM // exact match gives baseline of medium priority (127)
            ];
    }
}
