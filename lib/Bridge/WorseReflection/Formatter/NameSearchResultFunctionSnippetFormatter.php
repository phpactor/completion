<?php

namespace Phpactor\Completion\Bridge\WorseReflection\Formatter;

use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\ReferenceFinder\Search\NameSearchResult;
use Phpactor\WorseReflection\Reflector;

class NameSearchResultFunctionSnippetFormatter
{
    /**
     * @var ObjectFormatter
     */
    private $objectFormatter;

    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct(ObjectFormatter $objectFormatter, Reflector $reflector)
    {
        $this->objectFormatter = $objectFormatter;
        $this->reflector = $reflector;
    }

    public function canFormat(object $object): bool
    {
        return $object instanceof NameSearchResult
            && $object->type()->isFunction();
    }

    public function format(object $object): string
    {
        assert($object instanceof NameSearchResult);
        $functionName = $object->name()->__toString();
        $functionReflection = $this->reflector->reflectFunction($functionName);
        return $this->objectFormatter->format($functionReflection);
    }
}
