<?php

namespace Phpactor\Completion\Core;

class TypedCompletor
{
    /**
     * @var Completor
     */
    private $completor;

    /**
     * @var string[]
     */
    private $types;

    public function __construct(Completor $completor, array $types)
    {
        $this->completor = $completor;
        $this->types = $types;
    }

    public function types(): array
    {
        return $this->types;
    }

    public function completor(): Completor
    {
        return $this->completor;
    }
}
