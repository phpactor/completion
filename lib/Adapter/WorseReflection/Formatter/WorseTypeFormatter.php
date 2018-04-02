<?php

namespace Phpactor\Completion\Adapter\WorseReflection\Formatter;

use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\Core\Type;

class WorseTypeFormatter
{
    public function formatTypes(Types $types)
    {
        if (Types::empty() == $types) {
            return '<unknown>';
        }

        $formattedTypes = [];
        foreach ($types as $type) {
            $formattedTypes[] = $this->formatType($type);
        }

        return implode('|', $formattedTypes);
    }

    public function formatType(Type $type)
    {
        if (false === $type->isDefined()) {
            return '<unknown>';
        }

        $shortName = $type->short();

        if ($type->arrayType()->isDefined()) {
            // generic
            if ($type->isClass()) {
                return sprintf('%s<%s>', $shortName, $type->arrayType()->short());
            }

            // array
            return sprintf('%s[]', $type->arrayType()->short());
        }

        return $shortName;
    }
}
