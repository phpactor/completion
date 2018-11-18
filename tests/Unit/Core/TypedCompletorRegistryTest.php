<?php

namespace Phpactor\Completion\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\ChainCompletor;
use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\TypedCompletor;
use Phpactor\Completion\Core\TypedCompletorRegistry;

class TypedCompletorRegistryTest extends TestCase
{
    public function testReturnsCompletorsForAType()
    {
        $completor = $this->prophesize(Completor::class);
        $typedCompletor = new TypedCompletor($completor->reveal(), [ 'cucumber', 'gherkin' ]);
        $registry = new TypedCompletorRegistry([
            $typedCompletor
        ]);
        $completorForType = $registry->completorForType('cucumber');

        $completor->complete('foo', 123)->shouldBeCalled();

        $this->assertInstanceOf(ChainCompletor::class, $completorForType);

        iterator_to_array($completorForType->complete('foo', 123));
    }
}
