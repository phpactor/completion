<?php

namespace Phpactor\Completion\Adapter\WorseReflection\Completor\LocalVariable;

use Microsoft\PhpParser\Node\Expression\Variable as ParserVariable;
use Phpactor\WorseReflection\Core\Inference\Variable;

class VariableWithNode
{
    /**
     * @var Variable
     */
    private $variable;

    /**
     * @var Node
     */
    private $node;

    public function __construct(Variable $variable, ParserVariable $node)
    {
        $this->variable = $variable;
        $this->node = $node;
    }

    public function variable(): Variable
    {
        return $this->variable;
    }

    public function node(): ParserVariable
    {
        return $this->node;
    }
}
