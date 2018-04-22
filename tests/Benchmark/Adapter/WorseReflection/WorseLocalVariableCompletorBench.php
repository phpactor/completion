<?php

namespace Phpactor\Completion\Tests\Benchmark\Adapter\WorseReflection;

use Phpactor\Completion\Tests\Benchmark\CouldCompleteBenchCase;
use Phpactor\Completion\Core\Completor;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Completion\Bridge\WorseReflection\Completor\WorseClassMemberCompletor;
use Phpactor\Completion\Bridge\WorseReflection\Completor\WorseLocalVariableCompletor;

class WorseLocalVariableCompletorBench extends CouldCompleteBenchCase
{
    protected function create(string $source): Completor
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new WorseLocalVariableCompletor($reflector);
    }
}
