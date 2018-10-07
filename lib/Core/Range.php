<?php

namespace Phpactor\Completion\Core;

class Range
{
    /**
     * @var int
     */
    private $byteStart;

    /**
     * @var int
     */
    private $byteEnd;

    public function __construct(int $byteStart, int $byteEnd)
    {
        $this->byteStart = $byteStart;
        $this->byteEnd = $byteEnd;
    }

    public function start(): int
    {
        return $this->byteStart;
    }

    public function end(): int
    {
        return $this->byteEnd;
    }

}
