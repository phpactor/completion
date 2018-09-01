<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use LogicException;
use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\DelimitedList\ArgumentExpressionList;
use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\WorseReflection\Core\Exception\CouldNotResolveNode;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Inference\Variable as WorseVariable;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunctionLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Reflector;

class WorseConstructorCompletor extends AbstractParameterCompletor implements TolerantCompletor
{
    public function complete(Node $node, string $source, int $offset): Response
    {
        $response = Response::new();

        // Tolerant parser _seems_ to resolve f.e. offset 74 as the qualified
        // name of the node, when it is actually the open bracket. If it is a qualified
        // name, we take our chances on the parent.
        if ($node instanceof QualifiedName) {
            $node = $node->parent;
        }

        if ($node instanceof ArgumentExpressionList) {
            $node = $node->parent;
        }

        if (!$node instanceof Variable && !$node instanceof CallExpression) {
            return $response;
        }

        $creationExpression = $node->getFirstAncestor(ObjectCreationExpression::class);

        if (!$creationExpression) {
            return $response;
        }

        $variables = $this->variableCompletionHelper->variableCompletions($node, $source, $offset);

        // no variables available for completion, return empty handed
        if (empty($variables)) {
            return $response;
        }

        assert($creationExpression instanceof ObjectCreationExpression);

        $reflectionClass = $this->reflectClass($source, $creationExpression);

        if (null === $reflectionClass) {
            $response->issues()->add('Could not resolve reflection class');
            return $response;
        }

        if (false === $reflectionClass->methods()->has('__construct') ) {
            $response->issues()->add(sprintf('Class "%s" has no __construct', $reflectionClass->name()->__toString()));
            return $response;
        }

        $reflectionConstruct = $reflectionClass->methods()->get('__construct');

        // function has no parameters, return empty handed
        if ($reflectionConstruct->parameters()->count() === 0) {
            return $response;
        }

        return $this->populateResponse($response, $creationExpression, $reflectionConstruct, $variables);
    }

    private function reflectClass(string $source, ObjectCreationExpression $creationExpresion): ?ReflectionClass
    {
        $typeName = $creationExpresion->classTypeDesignator;

        if (!$typeName instanceof QualifiedName) {
            return null;
        }

        return $this->reflector->reflectClass((string) $typeName);
    }
}
