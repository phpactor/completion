<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\Qualifier;

use Closure;
use Microsoft\PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\TolerantQualifier;
use Phpactor\TestUtils\ExtractOffset;

abstract class TolerantQualifierTestCase extends TestCase
{
    /**
     * @dataProvider provideCouldComplete
     */
    public function testCouldComplete(string $source, Closure $assertion)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);

        $parser = new Parser();
        $root = $parser->parseSourceFile($source);
        $node = $root->getDescendantNodeAtPosition($offset);

        $assertion($this->createQualifier()->couldComplete($node));
    }

    abstract public function createQualifier(): TolerantQualifier;
}
