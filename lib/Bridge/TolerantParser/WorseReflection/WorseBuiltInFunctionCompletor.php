<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunction;
use Phpactor\WorseReflection\Core\Reflector\FunctionReflector;

class WorseBuiltInFunctionCompletor implements TolerantCompletor
{
    /**
     * @var FunctionReflector
     */
    private $functionReflector;

    /**
     * @var ObjectFormatter
     */
    private $formatter;


    public function __construct(FunctionReflector $functionReflector, ObjectFormatter $formatter)
    {
        $this->functionReflector = $functionReflector;
        $this->formatter = $formatter;
    }

    public function complete(Node $node, string $source, int $offset): Response
    {
        if (false === $node instanceof QualifiedName) {
            return Response::new();
        }

        $functionNames = $this->allFunctionNamesFor($node->getText());
        $functions = $this->functionReflections($functionNames);

        $suggestions = Suggestions::new();
        /** @var ReflectionFunction $functionReflection */
        foreach ($functions as $functionReflection) {
            $suggestions->add(Suggestion::create(
                'f', $functionReflection->name(), $this->formatter->format($functionReflection)
            ));
        }


        return Response::fromSuggestions($suggestions);
    }

    private function allFunctionNamesFor(string $partialName): Generator
    {
        $functions = get_defined_functions();
        
        foreach ($functions as $type => $functionNames) {
            foreach ($functionNames as $functionName) {
                if (0 === strpos($functionName, $partialName)) {
                    yield $functionName;
                }
            }
        }
    }

    private function functionReflections(Generator $functionNames): Generator
    {
        foreach ($functionNames as $functionName) {
            try {
                yield $this->functionReflector->reflectFunction($functionName);
            } catch (NotFound $e) {
            }
        }
    }
}
