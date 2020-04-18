<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\WorseReflection\Formatter;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\InterfaceFormatter;
use Phpactor\Completion\Tests\Integration\IntegrationTestCase;
use Phpactor\WorseReflection\ReflectorBuilder;

class InterfaceFormatterTest extends IntegrationTestCase
{
    public function testFormatsInterface()
    {
        $interface = ReflectorBuilder::create()->build()->reflectClassesIn('<?php namespace Bar {interface Foobar {}}')->first();
        self::assertTrue($this->formatter()->canFormat($interface));
        self::assertEquals('interface Bar\\Foobar', $this->formatter()->format($interface));
    }
}
