<?php

namespace Phpactor\Completion\Tests\Benchmark\Adapter\WorseReflection;

use Phpactor\Completion\Tests\Benchmark\CouldCompleteBenchCase;
use Phpactor\Completion\Core\CouldComplete;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Completion\Adapter\WorseReflection\Completor\WorseClassMemberCompletor;

class ClassMemberCompletorBench extends CouldCompleteBenchCase
{
    protected function create(string $source): CouldComplete
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new WorseClassMemberCompletor($reflector);
    }
}
