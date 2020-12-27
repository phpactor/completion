<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\DelimitedList\ArgumentExpressionList;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Token;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\Helper\NodeQuery;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflector\ClassReflector;
use Phpactor\WorseReflection\Reflector;

class WorseNamedParameterCompletor implements TolerantCompletor
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
    /**
     * {@inheritDoc}
     */
    public function complete(Node $node, TextDocument $source, ByteOffset $offset): Generator
    {
        if (null === $creation = NodeQuery::firstAncestorInVia(
            $node,
            [
                ObjectCreationExpression::class,
                CallExpression::class,
            ],
            [
                ArgumentExpression::class,
                ArgumentExpressionList::class
            ]
        )) {
            return true;
        }

        if ($creation instanceof ObjectCreationExpression) {
            return yield from $this->fromObjectCreation($creation);
        }

        if ($creation instanceof CallExpression) {
            return yield from $this->fromCallExpression($creation);
        }

        return true;
    }

    private function fromObjectCreation(ObjectCreationExpression $creation): Generator
    {
        $type = $creation->classTypeDesignator;

        if (!$type instanceof QualifiedName) {
            return true;
        }

        try {
            $class = $this->reflector->reflectClass((string)$type->getResolvedName());
        } catch (NotFound $e) {
            return true;
        }

        yield from $this->fromMethod($class, '__construct');

        return true;
    }

    private function fromMethod(ReflectionClass $class, string $method): Generator
    {
        if (!$class->methods()->has($method)) {
            return true;
        }

        foreach ($class->methods()->get($method)->parameters() as $parameter) {
            yield Suggestion::createWithOptions(
                sprintf('%s: ', $parameter->name()),
                [
                    'type' => Suggestion::TYPE_FIELD,
                    'short_description' => $this->formatter->format($parameter),
                ]
            );
        }
    }

    private function fromCallExpression(CallExpression $creation): Generator
    {
        try {
            $classLike = $this->reflector->reflectMethodCall(
                $creation->getFileContents(),
                $creation->callableExpression->getEndPosition()
            );
            yield from $this->fromMethod($classLike->class(), $classLike->name());
        } catch (NotFound $e) {
            return true;
        }

        return true;
    }
}
