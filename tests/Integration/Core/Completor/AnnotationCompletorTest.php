<?php

namespace Phpactor\Completion\Tests\Integration\Core\Completor;

use Phpactor\Completion\Core\Completor;
use Phpactor\Completion\Core\Completor\AnnotationCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\CompletorTestCase;
use Phpactor\ReferenceFinder\NameSearcher;
use Phpactor\ReferenceFinder\Search\NameSearchResult;
use Prophecy\Argument;

class AnnotationCompletorTest extends CompletorTestCase
{
    /**
     * @dataProvider provideAnnotationsToComplete()
     */
    public function testAnnotationCompletion(string $source, array $expectedSuggestions): void
    {
        $this->assertComplete($source, $expectedSuggestions);
    }

    public function provideAnnotationsToComplete(): iterable
    {
        yield 'Not an annotation' => [<<<EOT
<?php

class Annotation {}

/**
 * Ann<>
 */
class Foo {}
EOT
        , []
        ];

        yield 'Annotation w/o namespace' => [<<<EOT
<?php

class Annotation {}

/**
 * @Ann<>
 */
class Foo {}
EOT
        , [[
            'type' => Suggestion::TYPE_CLASS,
            'name' => 'Annotation',
            'short_description' => 'Annotation',
        ]]];

        yield 'Annotation on class' => [<<<EOT
<?php

class Annotation {}

/**
 * @Namespace\Ann<>
 */
class Foo {}
EOT
        , [[
            'type' => Suggestion::TYPE_CLASS,
            'name' => 'Annotation',
            'short_description' => 'Namespace\Annotation',
        ]]];
    }

    protected function createCompletor(string $source): Completor
    {
        $nameSearcher = $this->prophesize(NameSearcher::class);
        $nameSearcher->search(Argument::type('string'))
            ->willYield([])
        ;

        $nameSearcher->search('Ann')
            ->will(function ($args) {
                yield NameSearchResult::create('class', 'Annotation');

                return true;
            })
        ;

        $nameSearcher->search('Namespace\Ann')
            ->will(function ($args) {
                yield NameSearchResult::create('class', 'Namespace\Annotation');

                return true;
            })
        ;

        return new AnnotationCompletor(
            $nameSearcher->reveal(),
        );
    }
}
