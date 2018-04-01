<?php

namespace Phpactor\Completion;

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

    public function __construct(string $type, string $name, string $info)
    {
        $this->type = $type;
        $this->name = $name;
        $this->info = $info;
    }

    public static function create(string $type, string $name, string $info)
    {
        return new self($type, $name, $info);
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
}
