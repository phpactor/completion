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
        $tokens = token_get_all(mb_substr($source, 0, $offset));
        $tokens = array_reverse($tokens);

        $potential = false;
        foreach ($tokens as $token) {

            if (is_string($token) && $token == '$') {
                $potential = true;
                continue;
            }

            if (T_VARIABLE === $token[0]) {
                $potential = true;
                continue;
            }

            if ($potential) {
                if (T_DOUBLE_COLON === $token[0]) {
                    return false;
                }

                return true;
            }

            return false;
        }

        return $potential;
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
