<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\WorseReflection\Formatter;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\InterfaceFormatter;
use Phpactor\Completion\Tests\Integration\IntegrationTestCase;
use Phpactor\WorseReflection\ReflectorBuilder;

class TraitFormatterTest extends IntegrationTestCase
{
    public function testFormatsTrait()
    {
        $trait = ReflectorBuilder::create()->build()->reflectClassesIn('<?php namespace Bar {trait Foobar {}}')->first();
        self::assertTrue($this->formatter()->canFormat($trait));
        self::assertEquals('trait Bar\\Foobar', $this->formatter()->format($trait));
    }
}
