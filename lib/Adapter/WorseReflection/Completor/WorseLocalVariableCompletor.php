<?php

namespace Phpactor\Completion\Adapter\WorseReflection\Completor;

use Phpactor\Completion\Core\CouldComplete;
use Phpactor\Completion\Core\Response;
use Phpactor\WorseReflection\Reflector;
use Phpactor\Completion\Core\Suggestions;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Adapter\WorseReflection\Formatter\WorseTypeFormatter;
use Phpactor\WorseReflection\Core\Inference\Variable;
use Phpactor\WorseReflection\Core\Inference\Frame;

class WorseLocalVariableCompletor implements CouldComplete
{
    const NAME_REGEX = '{[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]}';
    const VALID_PRECHARS = [' ', '=', '[', '('];
    const INVALID_PRECHARS = [ ':' ];

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var WorseTypeFormatter
     */
    private $typeFormatter;

    public function __construct(Reflector $reflector, WorseTypeFormatter $typeFormatter = null)
    {
        $this->reflector = $reflector;
        $this->typeFormatter = $typeFormatter ?: new WorseTypeFormatter();
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
        $partialSource = mb_substr($source, 0, $offset);

        $dollarPosition = strrpos($partialSource, '$');
        if (false === $dollarPosition) {
            return Response::new();
        }

        $partialMatch = mb_substr($partialSource, $dollarPosition);
        $suggestions = Suggestions::new();
        $reflectionOffset = $this->reflector->reflectOffset($source, $offset);
        $frame = $reflectionOffset->frame();

        // Get all declared variables up until the offset. The most
        // recently declared variables should be first (which is why
        // we reverse the array).
        $reversedLocals = $this->orderedVariablesUntilOffset($frame, $offset);

        // Ignore variables that have already been suggested.
        $seen = [];

        /** @var Variable $local */
        foreach ($reversedLocals as $local) {

            if (isset($seen[$local->name()])) {
                continue;
            }

            $name = ltrim($partialMatch, '$');
            $matchPos = -1;

            if ($name) {
                $matchPos = mb_strpos($local->name(), $name);
            }

            if ('$' !== $partialMatch && 0 !== $matchPos) {
                continue;
            }

            $seen[$local->name()] = true;

            $suggestions->add(
                Suggestion::create(
                    'v',
                    $local->name(),
                    $this->typeFormatter->formatTypes($local->symbolContext()->types())
                )
            );
        }

        return Response::fromSuggestions($suggestions);
    }

    private function orderedVariablesUntilOffset(Frame $frame, int $offset)
    {
        return array_reverse(iterator_to_array($frame->locals()->lessThanOrEqualTo($offset)));
    }
}
