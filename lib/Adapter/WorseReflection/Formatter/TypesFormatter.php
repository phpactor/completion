<?php

namespace Phpactor\Completion\Adapter\WorseReflection\Formatter;

use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\Core\Type;

class TypesFormatter implements Formatter
{
    public function canFormat($object): bool
    {
        return $object instanceof Types;
    }

    public function format(ObjectFormatter $formatter, $object): string
    {
        assert($object instanceof Types);

        if (Types::empty() == $object) {
            return '<unknown>';
        }

        $formattedTypes = [];
        foreach ($object as $type) {
            $formattedTypes[] = $formatter->format($type);
        }

        return implode('|', $formattedTypes);
    }
}
