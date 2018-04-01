<?php

namespace Phpactor\Completion;

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

class Completor implements CanComplete
{
    /**
     * @var CouldComplete[]
     */
    private $completors;

    /**
     * @param CouldComplete[] $completors
     */
    public function __construct(array $completors)
    {
        $this->completors = $completors;
    }

    public function complete(string $source, int $offset): Response
    {
        $response = Response::create([]);

        foreach ($this->completors as $completor) {
            if ($completor->couldComplete($source, $offset)) {
                $response = $response->merge($completor->complete($source, $offset));
            }
        }

        return $response;
    }
}
