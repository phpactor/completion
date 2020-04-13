<?php

namespace Phpactor\Completion\Core;

class TypedCompletorRegistry
{
    /**
     * @var array<string, Completor>
     */
    private $completors;

    /**
     * Map should be from language ID to completors for that language:
     *
     * ```
     * [
     *     'php' => [
     *          // php completors
     *     ],
     *     'cucumber' => [
     *          // cucumber completors
     *     ],
     * ]
     * ```
     *
     * @param array<string, array<Completor>> $completorMap
     */
    public function __construct(array $completorMap)
    {
        foreach ($completorMap as $type => $completors) {
            foreach ($completors as $completor) {
                $this->add($type, $completor);
            }
        }
    }

    public function completorForType(string $type): Completor
    {
        if (!isset($this->completors[$type])) {
            return new ChainCompletor([]);
        }

        return new ChainCompletor($this->completors[$type]);
    }

    private function add(string $type, Completor $completor): void
    {
        if (!isset($this->completors[$type])) {
            $this->completors[$type] = [];
        }
        $this->completors[$type][] = $completor;
    }
}
