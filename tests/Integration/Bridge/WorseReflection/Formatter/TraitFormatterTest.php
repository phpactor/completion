<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\WorseReflection\Formatter;

use Phpactor\Completion\Tests\Integration\IntegrationTestCase;
use Phpactor\WorseReflection\ReflectorBuilder;

class TraitFormatterTest extends IntegrationTestCase
{
    public function testFormatsTrait()
    {
        $trait = ReflectorBuilder::create()->build()->reflectClassesIn('<?php namespace Bar {trait Foobar {}}')->first();
        self::assertTrue($this->formatter()->canFormat($trait));
        self::assertEquals('Bar\\Foobar (trait)', $this->formatter()->format($trait));
    }
}
