<?php

namespace Phpactor\Completion\Core;

/**
 * Completion types based on the language server protocol:
 * https://github.com/Microsoft/language-server-protocol/blob/gh-pages/specification.md#completion-request-leftwards_arrow_with_hook
 */
class SuggestionType
{
    public const TEXT = 'text';
    public const METHOD = 'method';
    public const FUNCTION = 'function';
    public const CONSTRUCTOR = 'constructor';
    public const FIELD = 'field';
    public const VARIABLE = 'variable';
    public const CLASS_ = 'class';
    public const INTERFACE = 'interface';
    public const MODULE = 'module';
    public const PROPERTY = 'property';
    public const UNIT = 'unit';
    public const VALUE = 'value';
    public const ENUM = 'enum';
    public const KEYWORD = 'keyword';
    public const SNIPPET = 'snippet';
    public const COLOR = 'color';
    public const FILE = 'file';
    public const REFERENCE = 'reference';
}
