<?php

namespace Phpactor\Completion\Adapter\WorseReflection\Completor;

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
use Phpactor\Completion\Core\CouldComplete;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Issues;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;
use Phpactor\Completion\Adapter\WorseReflection\Formatter\WorseTypeFormatter;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;

class WorseClassMemberCompletor implements CouldComplete
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var WorseTypeFormatter
     */
    private $formatter;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Reflector $reflector, Parser $parser = null, WorseTypeFormatter $formatter = null)
    {
        $this->reflector = $reflector;
        $this->formatter = $formatter ?: new WorseTypeFormatter();
        $this->parser = $parser ?: new Parser();
    }

    public function couldComplete(string $source, int $offset): bool
    {
        $offset = $this->rewindToLastNonWhitespaceChar($source, $offset);

        $node = $this->parser->parseSourceFile($source)->getDescendantNodeAtPosition($offset);

        if (null === $node) {
            return false;
        }

        $parentNode = $node->parent;

        if (
            $node instanceof MemberAccessExpression ||
            $node instanceof ScopedPropertyAccessExpression ||
            $parentNode instanceof MemberAccessExpression ||
            $parentNode instanceof ScopedPropertyAccessExpression
        ) {
            return true;
        }

        return false;
    }

    public function complete(string $source, int $offset): Response
    {
        list($offset, $partialMatch) = $this->getOffetToReflect($source, $offset);

        $reflectionOffset = $this->reflector->reflectOffset(
            SourceCode::fromString($source),
            Offset::fromint($offset)
        );

        $symbolContext = $reflectionOffset->symbolContext();
        $types = $symbolContext->types();

        $suggestions = new Suggestions();

        foreach ($types as $type) {
            $symbolContext = $this->populateSuggestions($symbolContext, $type, $suggestions);
        }

        $suggestions = $suggestions->startingWith($partialMatch);


        return new Response($suggestions, Issues::fromStrings($symbolContext->issues()));
    }

    private function getOffetToReflect($source, $offset)
    {
        /** @var string $source */
        $source = str_replace(PHP_EOL, ' ', $source);
        $untilCursor = substr($source, 0, $offset);

        $pos = strlen($untilCursor) - 1;
        $original = null;
        while ($pos) {
            if (in_array(substr($untilCursor, $pos, 2), [ '->', '::' ])) {
                $original = $pos;
                break;
            }
            $pos--;
        }

        $pos--;
        while (isset($untilCursor[$pos]) && $untilCursor[$pos] == ' ') {
            $pos--;
        }
        $pos++;

        $accessorOffset = ($original - $pos) + 2;
        $extra = trim(substr($untilCursor, $pos + $accessorOffset, $offset));

        return [ $pos,  $extra ];
    }

    private function getMethodInfo(ReflectionMethod $method)
    {
        $info = [
            substr((string) $method->visibility(), 0, 3),
            ' ',
            $method->name()
        ];

        if ($method->isAbstract()) {
            array_unshift($info, 'abstract ');
        }

        $paramInfos = [];

        /** @var ReflectionParameter $parameter */
        foreach ($method->parameters() as $parameter) {
            $paramInfo = [];
            if ($parameter->inferredTypes()->count()) {
                $paramInfo[] = $this->formatter->formatTypes($parameter->inferredTypes());
            }
            $paramInfo[] = '$' . $parameter->name();

            if ($parameter->default()->isDefined()) {
                $paramInfo[] = '= '. str_replace(PHP_EOL, '', var_export($parameter->default()->value(), true));
            }
            $paramInfos[] = implode(' ', $paramInfo);
        }
        $info[] = '(' . implode(', ', $paramInfos) . ')';

        $returnTypes = $method->inferredTypes();

        if ($returnTypes->count() > 0) {
            $info[] = ': ' . $this->formatter->formatTypes($returnTypes);
        }

        return implode('', $info);
    }

    private function getPropertyInfo(ReflectionProperty $property)
    {
        $info = [
            substr((string) $property->visibility(), 0, 3),
        ];

        if ($property->isStatic()) {
            $info[] = ' static';
        }

        $info[] = ' ';
        $info[] = '$' . $property->name();

        if ($property->inferredTypes()->best()->isDefined()) {
            $info[] = ': ' . $property->inferredTypes()->best()->short();
        }

        return implode('', $info);
    }

    private function populateSuggestions(SymbolContext $symbolContext, Type $type, Suggestions $suggestions): SymbolContext
    {
        if (false === $type->isDefined()) {
            return $symbolContext;
        }

        if ($type->isPrimitive()) {
            return $symbolContext->withIssue(sprintf('Cannot complete members on scalar value (%s)', (string) $type));
        }

        try {
            $classReflection = $this->reflector->reflectClassLike($type->className()->full());
        } catch (NotFound $e) {
            return $symbolContext->withIssue(sprintf('Could not find class "%s"', (string) $type));
        }

        $publicOnly = !in_array($symbolContext->symbol()->name(), ['this', 'self'], true);

        /** @var ReflectionMethod $method */
        foreach ($classReflection->methods() as $method) {
            if ($method->name() === '__construct') {
                continue;
            }
            if ($publicOnly && false === $method->visibility()->isPublic()) {
                continue;
            }
            $info = $this->getMethodInfo($method);
            $suggestions->add(Suggestion::create('f', $method->name(), $info));
        }

        if ($classReflection instanceof ReflectionClass) {
            foreach ($classReflection->properties() as $property) {
                if ($publicOnly && false === $property->visibility()->isPublic()) {
                    continue;
                }
                $suggestions->add(Suggestion::create('m', $property->name(), $this->getPropertyInfo($property)));
            }
        }

        if ($classReflection instanceof ReflectionClass ||
            $classReflection instanceof ReflectionInterface
        ) {
            /** @var ReflectionClass|ReflectionInterface */
            foreach ($classReflection->constants() as $constant) {
                $suggestions->add(Suggestion::create('m', $constant->name(), 'const ' . $constant->name()));
            }
        }

        return $symbolContext;
    }

    private function rewindToLastNonWhitespaceChar(string $source, int $offset)
    {
        while (!isset($source[$offset]) || $source[$offset] == ' ' || $source[$offset] == PHP_EOL) {
            $offset--;
        }

        return $offset;
    }
}
