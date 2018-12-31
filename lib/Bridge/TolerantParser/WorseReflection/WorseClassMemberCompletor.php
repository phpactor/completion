<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Token;
use Phpactor\Completion\Bridge\TolerantParser\Qualifier\ClassMemberQualifier;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\TolerantQualifiable;
use Phpactor\Completion\Bridge\TolerantParser\TolerantQualifier;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionProperty;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Reflector;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;

class WorseClassMemberCompletor implements TolerantCompletor, TolerantQualifiable
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

    public function qualifier(): TolerantQualifier
    {
        return new ClassMemberQualifier();
    }

    public function complete(Node $node, TextDocument $source, ByteOffset $offset): Generator
    {
        if ($node instanceof MemberAccessExpression) {
            $offset = $node->arrowToken->getFullStart();
        }

        if ($node instanceof ScopedPropertyAccessExpression) {
            $offset = $node->doubleColon->getFullStart();
        }


        assert($node instanceof MemberAccessExpression || $node instanceof ScopedPropertyAccessExpression);

        $memberName = $node->memberName;

        if ($memberName instanceof Variable) {
            $memberName = $memberName->name;
        }

        if (!$memberName instanceof Token) {
            return;
        }

        $partialMatch = (string) $memberName->getText($node->getFileContents());

        $reflectionOffset = $this->reflector->reflectOffset($source, $offset);

        $symbolContext = $reflectionOffset->symbolContext();
        $types = $symbolContext->types();
        $static = $node instanceof ScopedPropertyAccessExpression;

        foreach ($types as $type) {
            foreach ($this->populateSuggestions($symbolContext, $type, $static) as $suggestion) {
                if ($partialMatch && 0 !== mb_strpos($suggestion->name(), $partialMatch)) {
                    continue;
                }

                yield $suggestion;
            }
        }
    }

    private function populateSuggestions(SymbolContext $symbolContext, Type $type, bool $static): Generator
    {
        if (false === $type->isDefined()) {
            return;
        }

        if (null === $type->className()) {
            return;
        }

        if ($static) {
            yield Suggestion::createWithOptions('class', [
                'type' => Suggestion::TYPE_CONSTANT,
                'short_description' => $type->className(),
            ]);
        }

        try {
            $classReflection = $this->reflector->reflectClassLike($type->className()->full());
        } catch (NotFound $e) {
            return;
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

            if ($static && false === $method->isStatic()) {
                continue;
            }

            yield Suggestion::createWithOptions($method->name(), [
                'type' => Suggestion::TYPE_METHOD,
                'short_description' => $this->formatter->format($method),
            ]);
        }

        if ($classReflection instanceof ReflectionClass) {
            /** @var ReflectionProperty $property */
            foreach ($classReflection->properties() as $property) {
                if ($publicOnly && false === $property->visibility()->isPublic()) {
                    continue;
                }

                if ($static && false === $property->isStatic()) {
                    continue;
                }

                $name = $property->name();
                if ($static) {
                    $name = '$' . $name;
                }

                yield Suggestion::createWithOptions($name, [
                    'type' => Suggestion::TYPE_PROPERTY,
                    'short_description' => $this->formatter->format($property),
                ]);
            }
        }

        if ($classReflection instanceof ReflectionClass ||
            $classReflection instanceof ReflectionInterface
        ) {
            /** @var ReflectionClass|ReflectionInterface */
            foreach ($classReflection->constants() as $constant) {
                yield Suggestion::createWithOptions($constant->name(), [
                    'type' => Suggestion::TYPE_CONSTANT,
                    'short_description' => 'const ' . $constant->name(),
                ]);
            }
        }
    }
}
