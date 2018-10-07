<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\Qualifier;

use Generator;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Phpactor\Completion\Bridge\TolerantParser\Qualifier\ClassMemberQualifier;
use Phpactor\Completion\Bridge\TolerantParser\TolerantQualifier;

class ClassMemberQualifierTest extends TolerantQualifierTestCase
{
    public function provideCouldComplete(): Generator
    {
        yield 'non member access' => [
            '<?php $hello<>',
            function ($node) {
                $this->assertNull($node);
            }
        ];

        yield 'variable with previous accessor' => [
            '<?php $foobar->hello; $hello<>',
            function ($node) {
                $this->assertNull($node);
            }

        ];

        yield 'variable with previous accessor' => [
            '<?php $foobar->hello; $hello<>',
            function ($node) {
                $this->assertNull($node);
            }

        ];

        yield 'statement with previous member access' => [
            '<?php if ($foobar && $this->foobar) { echo<>',
            function ($node) {
                $this->assertNull($node);
            }
        ];

        yield 'variable with previous static member access' => [
            '<?php Hello::hello(); $foo<>',
            function ($node) {
                $this->assertNull($node);
            }
        ];

        yield 'returns the scoped property access expression' => [
            '<?php Hello::<>',
            function ($node) {
                $this->assertInstanceOf(ScopedPropertyAccessExpression::class, $node);
            }
        ];

        yield 'returns the scoped property access expression parent' => [
            '<?php Hello::FO<>',
            function ($node) {
                $this->assertInstanceOf(ScopedPropertyAccessExpression::class, $node);
            }
        ];
    }

    public function createQualifier(): TolerantQualifier
    {
        return new ClassMemberQualifier();
    }
}
