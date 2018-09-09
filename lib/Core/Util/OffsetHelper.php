<?php

namespace Phpactor\Completion\Core\Util;

class OffsetHelper
{
    public static function lastNonWhitespaceCharacterOffset(string $source): int
    {
        return mb_strlen((string) preg_replace('/[ \t\x0d\n\r\f]+$/u', '', $source));
    }
}
