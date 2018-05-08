<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Microsoft\PhpParser\Node;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\MethodFormatter;
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
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Issues;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;

class WorseClassMemberCompletor implements TolerantCompletor
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
        if (false === $this->couldComplete($node, $source, $offset)) {
            return Response::new();
        }

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
        $untilCursor = mb_substr($source, 0, $offset);

        $pos = mb_strlen($untilCursor) - 1;
        $original = null;
        while ($pos) {
            if (in_array(mb_substr($untilCursor, $pos, 2), [ '->', '::' ])) {
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
        $extra = trim(mb_substr($untilCursor, $pos + $accessorOffset, $offset));

        return [ $pos,  $extra ];
    }

    private function populateSuggestions(SymbolContext $symbolContext, Type $type, Suggestions $suggestions): SymbolContext
    {
        if (false === $type->isDefined()) {
            return $symbolContext;
        }

        if (null === $type->className()) {
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
            $info = $this->formatter->format($method);
            $prose = $method->docblock()->formatted();
            $suggestions->add(Suggestion::create('f', $method->name(), $info, $prose));
        }

        if ($classReflection instanceof ReflectionClass) {
            foreach ($classReflection->properties() as $property) {
                if ($publicOnly && false === $property->visibility()->isPublic()) {
                    continue;
                }
                $prose = $property->docblock()->formatted();
                $suggestions->add(Suggestion::create('m', $property->name(), $this->formatter->format($property), $prose));
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

    private function couldComplete(Node $node, string $source, int $offset): bool
    {
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
}
