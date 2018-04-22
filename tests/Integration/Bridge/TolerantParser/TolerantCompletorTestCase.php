<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser;

use Phpactor\Completion\Bridge\TolerantParser\ChainTolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Tests\Integration\CompletorTestCase;

abstract class TolerantCompletorTestCase extends CompletorTestCase
{
    abstract protected function createTolerantCompletor(string $source): TolerantCompletor;

    protected function createCompletor(string $source): Completor
    {
        return new ChainTolerantCompletor([
            $this->createTolerantCompletor($source)
        ]);
    }
}
