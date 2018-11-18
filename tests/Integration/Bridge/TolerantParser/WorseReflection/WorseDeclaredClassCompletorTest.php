<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use Generator;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseDeclaredClassCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\ReflectorBuilder;

class WorseDeclaredClassCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(string $source): TolerantCompletor
    {
        $reflector = ReflectorBuilder::create()
            ->addLocator(new StubSourceLocator(
                ReflectorBuilder::create()->build(),
                __DIR__ . '/../../../../../vendor/jetbrains/phpstorm-stubs',
                __DIR__ . '/../../../../../cache'
            ))
            ->addSource($source)
            ->build();

        return new WorseDeclaredClassCompletor($reflector, $this->formatter());
    }

    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        $this->assertComplete($source, $expected);
    }

    public function provideComplete(): Generator
    {
        yield 'array object' => [
            <<<'EOT'
<?php

$class = new Exception<>
EOT
        ,
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'Exception',
                    'short_description' => 'Exception(string $message = \'\', int $code = 0, Throwable $previous = NULL)',
                ]
            ]
        ];
    }
}
