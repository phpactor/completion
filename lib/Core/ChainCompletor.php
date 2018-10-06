<?php

namespace Phpactor\Completion\Core;

use Generator;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Core\Offset;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;
use Phpactor\WorseReflection\Core\Reflection\ReflectionProperty;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Reflector;

class ChainCompletor implements Completor
{
    /**
     * @var Completor[]
     */
    private $completors;

    /**
     * @param Completor[] $completors
     */
    public function __construct(array $completors)
    {
        $this->completors = $completors;
    }

    public function complete(string $source, int $offset): Generator
    {
        foreach ($this->completors as $completor) {
            foreach ($completor->complete($source, $offset) as $suggestion) {
                yield $suggestion;
            }
        }
    }
}
