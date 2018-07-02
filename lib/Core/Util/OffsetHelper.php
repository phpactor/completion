<?php

namespace Phpactor\Completion\Core\Util;

class OffsetHelper
{
    public static function lastNonWhitespaceOffset(string $source): int
    {
        // break the string into an array of single (possibly
        // multi-byte) characters
        $chars = array_map(function ($index) use ($source) {
            return mb_substr($source, $index, 1);
        }, range(0, mb_strlen($source)));

        // if there are no characters, then just return zero
        if (count($chars) === 0) {
            return 0;
        }

        // pop all empty or whitespace-like characters from the
        // end of the array
        $index = count($chars) - 1;
        while($chars) {
            $char = $chars[$index--];
            if (0 !== mb_strlen($char) && false === ctype_space($char)) {
                break;
            }

            array_pop($chars);
        }

        // determine the offset based on the multi-byte length of
        // the remaining elements
        return mb_strlen(implode('', $chars));
    }
}
