<?php

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Parser;
use Phpactor\Completion\Core\Exception\CouldNotHelpWithSignature;
use Phpactor\Completion\Core\Formatter\Formatter;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Core\ParameterInformation;
use Phpactor\Completion\Core\SignatureHelp;
use Phpactor\Completion\Core\SignatureHelper;
use Phpactor\Completion\Core\SignatureInformation;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
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
    ): SignatureHelp
    {
        $rootNode = $this->parser->parseSourceFile($textDocument->__toString());
        $node = $rootNode->getDescendantNodeAtPosition($offset->toInt());

        if (!$node instanceof CallExpression) {
            throw new CouldNotHelpWithSignature(sprintf('Could not provide signature for AST node of type "%s"', get_class($node)));
        }

        $callable = $node->callableExpression;
        if ($callable instanceof QualifiedName) {
            $signatures = [];
            $name = $callable->__toString();
            $functionReflection = $this->reflector->reflectFunction($name);

            $parameters = [];

            /** @var ReflectionParameter $parameter */
            foreach ($functionReflection->parameters() as $parameter) {
                $formatted = $this->formatter->format($parameter);
                $parameters[] = new ParameterInformation($parameter->name(), $formatted);
            }

            $formatted = $this->formatter->format($functionReflection);
            $signatures[] = new SignatureInformation($formatted, $parameters);

            return new SignatureHelp($signatures, 0);
        }
    }
}
