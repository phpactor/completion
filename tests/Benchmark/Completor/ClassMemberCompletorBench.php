<?php

namespace Phpactor\Completion\Tests\Benchmark\Completor;

use Phpactor\Completion\Tests\Benchmark\CouldCompleteBenchCase;
use Phpactor\Completion\CouldComplete;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Completion\Completor\ClassMemberCompletor;

class ClassMemberCompletorBench extends CouldCompleteBenchCase
{
    protected function create(string $source): CouldComplete
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new ClassMemberCompletor($reflector);
    }
}
