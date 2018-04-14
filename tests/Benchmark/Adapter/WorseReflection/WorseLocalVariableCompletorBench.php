<?php

namespace Phpactor\Completion\Tests\Benchmark\Adapter\WorseReflection;

use Phpactor\Completion\Tests\Benchmark\CouldCompleteBenchCase;
use Phpactor\Completion\Core\CouldComplete;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Completion\Adapter\WorseReflection\Completor\WorseClassMemberCompletor;
use Phpactor\Completion\Adapter\WorseReflection\Completor\WorseLocalVariableCompletor;

class WorseLocalVariableCompletorBench extends CouldCompleteBenchCase
{
    protected function create(string $source): CouldComplete
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new WorseLocalVariableCompletor($reflector);
    }
}
