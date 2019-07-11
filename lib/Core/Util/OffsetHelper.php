<?php

namespace Phpactor\Completion\Core\Util;

use RuntimeException;

class OffsetHelper
{
    public static function lastNonWhitespaceCharacterOffset(string $source): int
    {
        $source = preg_replace('/[ \t\x0d\n\r\f]+$/u', '', $source);

        if (null === $source) {
            throw new RuntimeException('preg_replace could not parse string');
        }

        return mb_strlen($source);
    }
}
