<?php

namespace Phpactor\Completion\Tests\Benchmark;

use Phpactor\Completion\Core\Completor;
use Phpactor\TestUtils\ExtractOffset;

abstract class CouldCompleteBenchCase
{
    private $source;
    private $offset;

    /**
     * @var CouldComplete
     */
    private $completor;

    protected abstract function create(string $source): Completor;

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
     * @Revs(100)
     * @Iterations(10)
     * @OutputTimeUnit("milliseconds")
     */
    public function benchComplete($params)
    {
        $this->completor->complete($this->source, $this->offset);
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
