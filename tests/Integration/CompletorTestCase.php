<?php

namespace Phpactor\Completion\Tests\Integration;

use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

abstract class CompletorTestCase extends IntegrationTestCase
{
    abstract protected function createCompletor(string $source): Completor;

    protected function assertComplete(string $source, array $expected, bool $isComplete = true): void
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $suggestionGenerator = $completor->complete(
            TextDocumentBuilder::create($source)->language('php')->uri('file:///tmp/test')->build(),
            ByteOffset::fromInt($offset)
        );
        $suggestions = iterator_to_array($suggestionGenerator);
        usort($suggestions, function (Suggestion $suggestion1, Suggestion $suggestion2) {
            return $suggestion1->name() <=> $suggestion2->name();
        });

        $this->assertCount(count($expected), $suggestions);
        foreach ($expected as $index => $expectedSuggestion) {
            $this->assertArraySubset($expectedSuggestion, $suggestions[$index]->toArray());
        }

        $this->assertCount(count($expected), $suggestions);
        $this->assertEquals($isComplete, $suggestionGenerator->getReturn(), '"is complete" was as expected');
    }

    public function assertCouldNotComplete(string $source): void
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $suggestions = $completor->complete(
            TextDocumentBuilder::create($source)->language('php')->uri('file:///tmp/test')->build(),
            ByteOffset::fromInt($offset)
        );

        $this->assertEmpty(iterator_to_array($suggestions));
        $this->assertTrue($suggestions->getReturn());
    }
}
