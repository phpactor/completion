<?php

namespace Phpactor\Completion\Core\Completor;

use Generator;
use Phpactor\Completion\Core\Completor;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\Util\WordAtOffset;

class AnnotationCompletor implements Completor
{
    use NameSearcherCompletor;

    /**
     * @var NameSearcher
     */
    private $nameSearcher;

    public function __construct(NameSearcher $nameSearcher)
    {
        $this->nameSearcher = $nameSearcher;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(TextDocument $source, ByteOffset $byteOffset): Generator
    {
        $annotation = WordAtOffset::annotation($source, $byteOffset->toInt());

        if (0 !== strpos($annotation, '@')) {
            return true;
        }

        $suggestions = $this->completeName(ltrim($annotation, '@'));

        yield from $suggestions;

        return $suggestions->getReturn();
    }

    protected function getSearcher(): NameSearcher
    {
        return $this->nameSearcher;
    }
}
