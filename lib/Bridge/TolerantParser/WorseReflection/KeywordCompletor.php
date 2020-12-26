<?php

declare(strict_types=1);

namespace Phpactor\Completion\Bridge\TolerantParser\WorseReflection;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\TokenStringMaps;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use function get_class;

class KeywordCompletor implements TolerantCompletor
{
    /**
    * {@inheritDoc}
    */
    public function complete(Node $node, TextDocument $source, ByteOffset $offset): Generator
    {
        $parents = [
            get_class($node)
        ];
        $parent = $node;
        while(($parent = $parent->getParent()) !== null){
            $parents[] = get_class($parent);
        }

        dump(implode("\n    ->", $parents));
        
        if($node->getParent() instanceof ClassDeclaration){
            /** @var ClassDeclaration $declaration */
            $declaration = $node->getParent();
            $nameEnd = $declaration->name->getEndPosition();
            // dump("name end: {$nameEnd}, offset: {$offset->toInt()}");
        } else if($node instanceof MemberAccessExpression){
            /** @var MemberAccessExpression $memberAccess */
            $memberAccess = $node;
            $nameEnd = $memberAccess->memberName->getEndPosition();
            if($nameEnd == $offset->toInt())
                return true;
        }
        foreach (array_merge(array_keys(TokenStringMaps::RESERVED_WORDS), array_keys(TokenStringMaps::KEYWORDS)) as $keyword) {
            yield Suggestion::createWithOptions(
                $keyword,
                [
                    'type' => Suggestion::TYPE_KEYWORD
                ]
            );
        }
        return true;
    }
}
