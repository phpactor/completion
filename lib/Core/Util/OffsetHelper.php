<?php

namespace Phpactor\Completion\Core\Util;

use RuntimeException;

class OffsetHelper
{
    public static function lastNonWhitespaceCharacterOffset(string $input): int
    {
        $source = preg_replace('/[ \t\x0d\n\r\f]+$/u', '', $input);

        if (null === $source) {
            throw new RuntimeException(sprintf(
                'preg_replace could not parse string "%s"',
                $input
            ));
        }

        return mb_strlen($source);
    }
}
