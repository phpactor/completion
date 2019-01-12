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

    protected function assertComplete(string $source, array $expected)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $suggestions = iterator_to_array($completor->complete(
            TextDocumentBuilder::create($source)->language('php')->build(),
            ByteOffset::fromInt($offset)
        ));
        usort($suggestions, function (Suggestion $suggestion1, Suggestion $suggestion2) {
            return $suggestion1->name() <=> $suggestion2->name();
        });

        $this->assertCount(count($expected), $suggestions);
        foreach ($expected as $index => $expectedSuggestion) {
            $this->assertArraySubset($expectedSuggestion, $suggestions[$index]->toArray());
        }

        $this->assertCount(count($expected), $suggestions);
    }

    public function assertCouldNotComplete(string $source)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $completor = $this->createCompletor($source);
        $result = $completor->complete(
            TextDocumentBuilder::create($source)->language('php')->build(),
            ByteOffset::fromInt($offset)
        );

        $this->assertEmpty(iterator_to_array($result));
    }
}
