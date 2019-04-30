<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Microsoft\PhpParser\Node\DelimitedList\ArgumentExpressionList;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Token;
use Phpactor\Completion\Core\Exception\CouldNotHelpWithSignature;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\ParameterInformation;
use Phpactor\Completion\Core\SignatureHelp;
use Phpactor\Completion\Core\SignatureHelper;
use Phpactor\Completion\Core\SignatureInformation;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunctionLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;
use Phpactor\WorseReflection\Reflector;

class WorseSignatureHelper implements SignatureHelper
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var ObjectFormatter
     */
    private $formatter;

    public function __construct(Reflector $reflector, ObjectFormatter $formatter, ?Parser $parser = null)
    {
        $this->reflector = $reflector;
        $this->parser = $parser ?: new Parser();
        $this->formatter = $formatter;
    }

    public function signatureHelp(
        TextDocument $textDocument,
        ByteOffset $offset
    ): SignatureHelp {
        $rootNode = $this->parser->parseSourceFile($textDocument->__toString());
        $nodeAtPosition = $node = $rootNode->getDescendantNodeAtPosition($offset->toInt());

        if (!$nodeAtPosition instanceof CallExpression) {
            $node = $node->getFirstAncestor(CallExpression::class);
        }

        if (is_null($node)) {
            throw new CouldNotHelpWithSignature(sprintf('Could not provide signature for AST node of type "%s"', get_class($nodeAtPosition)));
        }

        assert($node instanceof CallExpression);

        $position = 0;
        if ($nodeAtPosition instanceof ArgumentExpressionList) {
            $text = $nodeAtPosition->getText();
            $position = substr_count($text, ',');
        }

        /** @var Node|QualfifiedName $callable */
        $callable = $node->callableExpression;

        if ($callable instanceof QualifiedName) {
            return $this->signatureHelpForFunction($callable, $position);
        }

        if ($callable instanceof ScopedPropertyAccessExpression) {
            return $this->signatureHelpForScopedPropertyAccess($callable, $node, $position);
        }

        if ($callable instanceof MemberAccessExpression) {
            $reflectionOffset = $this->reflector->reflectOffset($textDocument, $callable->getEndPosition());
            $symbolContext = $reflectionOffset->symbolContext();

            if ($symbolContext->symbol()->symbolType() !== Symbol::METHOD) {
                throw new CouldNotHelpWithSignature(sprintf(
                    'Could not provide signature member type "%s"',
                    $symbolContext->symbol()->symbolType()
                ));
            }

            $containerType = $symbolContext->containerType();

            if (null === $containerType) {
                throw new CouldNotHelpWithSignature(sprintf(
                    'Could not determine container type for: "%s"',
                    $symbolContext->symbol()->name()
                ));
            }

            if (!$containerType->isClass()) {
                throw new CouldNotHelpWithSignature(sprintf(
                    'Container type is not a class: "%s"',
                    $symbolContext->symbol()->name()
                ));
            }

            $reflectionClass = $this->reflector->reflectClassLike($containerType->className());
            $reflectionMethod = $reflectionClass->methods()->get($symbolContext->symbol()->name());

            return $this->createSignatureHelp($reflectionMethod, $position);
        }

        throw new CouldNotHelpWithSignature(sprintf('Could not provide signature for AST node of type "%s"', get_class($callable)));
    }

    private function signatureHelpForFunction(QualifiedName $callable, int $position): SignatureHelp
    {
        $name = $callable->__toString();
        $functionReflection = $this->reflector->reflectFunction($name);

        return $this->createSignatureHelp($functionReflection, $position);
    }

    private function createSignatureHelp(ReflectionFunctionLike $functionReflection, int $position)
    {
        $signatures = [];
        $parameters = [];

        /** @var ReflectionParameter $parameter */
        foreach ($functionReflection->parameters() as $parameter) {
            $formatted = $this->formatter->format($parameter);
            $parameters[] = new ParameterInformation($parameter->name(), $formatted);
        }

        $formatted = $this->formatter->format($functionReflection);
        $signatures[] = new SignatureInformation($formatted, $parameters);

        return new SignatureHelp($signatures, $position);
    }

    private function signatureHelpForScopedPropertyAccess(ScopedPropertyAccessExpression $callable, CallExpression $node, int $position)
    {
        $scopeResolutionQualifier = $callable->scopeResolutionQualifier;

        if (!$scopeResolutionQualifier instanceof QualifiedName) {
            throw new CouldNotHelpWithSignature(sprintf('Static calls only supported with qualified names'));
        }

        $class = $scopeResolutionQualifier->getResolvedName();

        $reflectionClass = $this->reflector->reflectClass((string) $class);

        $memberName = $callable->memberName;

        if (!$memberName instanceof Token) {
            throw new CouldNotHelpWithSignature('Variable member names not supported');
        }

        $memberName = $memberName->getText($node->getFileContents());
        $reflectionMethod = $reflectionClass->methods()->get((string) $memberName);

        return $this->createSignatureHelp($reflectionMethod, $position);
    }
}
