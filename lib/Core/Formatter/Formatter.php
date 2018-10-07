<?php

namespace Phpactor\Completion\Core\Formatter;

interface Formatter
{
    public function canFormat($object): bool;

    public function format(ObjectFormatter $formatter, $object): string;
}
