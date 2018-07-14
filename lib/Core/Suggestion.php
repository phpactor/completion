<?php

namespace Phpactor\Completion\Core;

use RuntimeException;

class Suggestion
{
    const TYPE_FUNCTION = 'f';
    const TYPE_CLASS_MEMBER = 'm';
    const TYPE_VARIABLE = 'v';
    const TYPE_CONSTANT = 'm';
    const TYPE_UNDEFINED = 'u';
    const TYPE_CLASS = 't';

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
     * @var string|null
     */
    private $classImport;

    private function __construct(
        string $name,
        string $type = Suggestion::TYPE_UNDEFINED,
        string $info = '',
        string $classImport = null
    )
    {
        $this->type = $type;
        $this->name = $name;
        $this->info = $info;
        $this->classImport = $classImport;
    }

    public static function create(string $name)
    {
        return new self($name);
    }

    public static function createWithOptions(string $name, array $options): self
    {
        $defaults = [
            'short_description' => '',
            'type' => '',
            'class_import' => null,
        ];

        if ($diff = array_diff(array_keys($options), array_keys($defaults))) {
            throw new RuntimeException(sprintf(
                'Invalid options for suggestion: "%s" valid options: "%s"',
                implode('", "', $diff), implode('", "', array_keys($defaults))
            ));
        }

        $options = array_merge($defaults, $options);

        return new self(
            $name,
            $options['type'],
            $options['short_description'],
            $options['class_import']
        );
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

    /**
     * @return string|null
     */
    public function classImport()
    {
        return $this->classImport;
    }
}
