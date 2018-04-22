<?php

namespace Phpactor\Completion\Tests\Benchmark\Adapter\WorseReflection;

use Phpactor\Completion\Tests\Benchmark\CouldCompleteBenchCase;
use Phpactor\Completion\Core\Completor;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Completion\Adapter\WorseReflection\Completor\WorseClassMemberCompletor;

class ClassMemberCompletorBench extends CouldCompleteBenchCase
{
    protected function create(string $source): Completor
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new WorseClassMemberCompletor($reflector);
    }
}
