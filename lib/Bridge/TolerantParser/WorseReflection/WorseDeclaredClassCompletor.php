<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Generator;
use Microsoft\PhpParser\Node;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Completor;
use Phpactor\WorseReflection\Core\Reflector\ClassReflector;

class WorseDeclaredClassCompletor implements TolerantCompletor
{
    /**
     * @var ClassReflector
     */
    private $reflector;

    public function __construct(ClassReflector $reflector)
    {
        $this->reflector = $reflector;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(Node $node, string $source, int $offset): Generator
    {
    }
}
