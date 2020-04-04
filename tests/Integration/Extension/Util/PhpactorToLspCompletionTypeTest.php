<?php

namespace Phpactor\Completion\Tests\Integration\Extension\Util;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Extension\Util\PhpactorToLspCompletionType;
use ReflectionClass;

class PhpactorToLspCompletionTypeTest extends TestCase
{
    public function testConverts()
    {
        $reflection = new ReflectionClass(Suggestion::class);
        foreach ($reflection->getConstants() as $constantValue) {
            $this->assertNotNull(PhpactorToLspCompletionType::fromPhpactorType($constantValue));
        }
    }
}
