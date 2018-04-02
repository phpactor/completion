<?php

namespace Phpactor\Completion\Tests\Benchmark;

use Phpactor\Completion\Core\CouldComplete;
use Phpactor\TestUtils\ExtractOffset;

abstract class CouldCompleteBenchCase
{
    private $source;
    private $offset;

    /**
     * @var CouldComplete
     */
    private $completor;

    protected abstract function create(string $source): CouldComplete;

    public function setUp($params)
    {
        $source = file_get_contents(__DIR__ . '/' . $params['source']);
        list($source, $offset) = ExtractOffset::fromSource($source);
        $this->source = $source;
        $this->offset = $offset;
        $this->completor = $this->create($source);
    }
    /**
     * @ParamProviders({"provideCouldComplete"})
     * @BeforeMethods({"setUp"})
     * @Revs(1000)
     * @Iterations(10)
     */
    public function benchCouldComplete($params)
    {
        $this->completor->couldComplete($this->source, $this->offset);
    }

    public function provideCouldComplete()
    {
        return [
            'short' => [
                'source' => 'Code/Short.php.test',
            ],
            'long' => [
                'source' => 'Code/Example1.php.test',
            ],
        ];
    }
}
