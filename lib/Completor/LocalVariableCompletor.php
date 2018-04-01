<?php

namespace Phpactor\Completion\Completor;

use Phpactor\Completion\CouldComplete;
use Phpactor\Completion\Response;
use Phpactor\WorseReflection\Reflector;
use Phpactor\Completion\Suggestions;
use Phpactor\Completion\Suggestion;

class LocalVariableCompletor implements CouldComplete
{
    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    public function couldComplete(string $source, int $offset): bool
    {
        $char = mb_substr($source, $offset - 1, 1);

        return $char === '$';
    }

    public function complete(string $source, int $offset): Response
    {
        $suggestions = Suggestions::new();
        $reflectionOffset = $this->reflector->reflectOffset($source, $offset);
        $frame = $reflectionOffset->frame();

        foreach ($frame->locals() as $local) {
            $suggestions->add(
                Suggestion::create(
                    'v',
                    '$' . $local->name(),
                    $local->symbolContext()->types()->best()->__toString()
                )
            );
        }

        return Response::fromSuggestions($suggestions);
    }
}
