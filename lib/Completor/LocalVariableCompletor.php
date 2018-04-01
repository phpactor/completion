<?php

namespace Phpactor\Completion\Completor;

use Phpactor\Completion\CouldComplete;
use Phpactor\Completion\Response;
use Phpactor\WorseReflection\Reflector;
use Phpactor\Completion\Suggestions;
use Phpactor\Completion\Suggestion;

class LocalVariableCompletor implements CouldComplete
{
    const NAME_REGEX = '{[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]}';
    const VALID_PRECHARS = [' ', '=', '[', '('];
    const INVALID_PRECHARS = [ ':' ];

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
        $source = mb_substr($source, 0, $offset);

        $potentiallyVariable = false;
        for ($count = 1; $count < mb_strlen($source); $count++) {
            $char = mb_substr($source, -$count, 1);
            if ($char === '$') {
                $potentiallyVariable = true;
                continue;
            }

            if ($potentiallyVariable && in_array($char, self::VALID_PRECHARS)) {
                return true;
            }

            if ($potentiallyVariable && false === in_array($char, self::INVALID_PRECHARS)) {
                return false;
            }
        }

        return false;
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
