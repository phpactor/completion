<?php

namespace Phpactor\Completion\Core\Formatter;

use Phpactor\Completion\Core\Exception\CouldNotFormat;

class ObjectFormatter
{
    /**
     * @var Formatter[]
     */
    private $formatters = [];

    /**
     * @param Formatter[] $formatters
     */
    public function __construct(array $formatters = [])
    {
        foreach ($formatters as $formatter) {
            $this->add($formatter);
        }
    }

    public function format($object): string
    {
        foreach ($this->formatters as $formatter) {
            if (false === $formatter->canFormat($object)) {
                continue;
            }

            return $formatter->format($this, $object);
        }

        throw new CouldNotFormat(sprintf(
            'Do not know how to format "%s"',
            get_class($object)
        ));
    }

    public function canFormat($object): bool
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->canFormat($object)) {
                return true;
            }
        }

        return false;
    }

    private function add(Formatter $formatter)
    {
        $this->formatters[] = $formatter;
    }
}
