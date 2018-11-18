<?php

namespace Phpactor\Completion\Core;

class TypedCompletorRegistry
{
    /**
     * @var array<array<Completor>>
     */
    private $completors;

    /**
     * @param TypedCompletor[] $completors
     */
    public function __construct(array $completors)
    {
        foreach ($completors as $completor) {
            $this->add($completor);
        }
    }

    public function completorForType(string $type): Completor
    {
        if (!isset($this->completors[$type])) {
            return new ChainCompletor([]);
        }

        return new ChainCompletor($this->completors[$type]);
    }

    private function add(TypedCompletor $completor): void
    {
        foreach ($completor->types() as $type) {
            if (!isset($this->completors[$type])) {
                $this->completors[$type] = [];
            }
            $this->completors[$type][] = $completor->completor();
        }
    }
}
