<?php

namespace Phpactor\Completion\Core;

class Suggestion
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $info;

    /**
     * @var string
     */
    private $prose;

    public function __construct(string $type, string $name, string $info, string $prose = '')
    {
        $this->type = $type;
        $this->name = $name;
        $this->info = $info;
        $this->prose = $prose;
    }

    public static function create(string $type, string $name, string $info, string $prose = '')
    {
        return new self($type, $name, $info, $prose);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function info(): string
    {
        return $this->info;
    }

    public function prose(): string
    {
        return $this->prose;
    }
}
