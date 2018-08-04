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
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunction;
use Phpactor\WorseReflection\Core\Reflector\FunctionReflector;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Phpactor\WorseReflection\Reflector;

class WorseFunctionCompletor implements TolerantCompletor
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var ObjectFormatter
     */
    private $formatter;

    public function __construct(Reflector $reflector, ObjectFormatter $formatter)
    {
        $this->reflector = $reflector;
        $this->formatter = $formatter;
    }

    public function complete(Node $node, string $source, int $offset): Response
    {
        if (false === $node instanceof QualifiedName) {
            return Response::new();
        }

        $functionNames = $this->reflectedFunctions($source);
        $functionNames = $this->definedNamesFor($functionNames, $node->getText());
        $functions = $this->functionReflections($functionNames);

        $suggestions = Suggestions::new();
        /** @var ReflectionFunction $functionReflection */
        foreach ($functions as $functionReflection) {
            $suggestions->add(Suggestion::createWithOptions(
                $functionReflection->name()->short(),
                [
                    'type' => Suggestion::TYPE_FUNCTION,
                    'short_description' => $this->formatter->format($functionReflection),
                ]
            ));
        }


        return Response::fromSuggestions($suggestions);
    }

    private function definedNamesFor(array $reflectedFunctions, string $partialName): Generator
    {
        $functions = get_defined_functions();
        $functions['reflected'] = $reflectedFunctions;
        
        return $this->filterFunctions($functions, $partialName);
    }

    private function reflectedFunctions(string $source)
    {
        $functionNames = [];
        foreach ($this->reflector->reflectFunctionsIn($source) as $function) {
            $functionNames[] = $function->name()->full();
        }

        return $functionNames;
    }

    private function filterFunctions(array $functions, string $partialName): Generator
    {
        foreach ($functions as $type => $functionNames) {
            foreach ($functionNames as $functionName) {
                $functionName = Name::fromString($functionName);
                if (0 === strpos($functionName->short(), $partialName)) {
                    yield $functionName;
                }
            }
        }
    }

    private function functionReflections(Generator $functionNames): Generator
    {
        foreach ($functionNames as $functionName) {
            try {
                yield $this->reflector->reflectFunction($functionName);
            } catch (NotFound $e) {
            }
        }
    }
}
