<?php

// add stubs for PHPUnit < 8
// when all packages are using PHPUnit 9 these can be removed

namespace DMS\PHPUnitExtensions\ArraySubset{
    if (!class_exists(ArraySubsetAsserts::class)) {
        trait ArraySubsetAsserts {}
    }
}

namespace Prophecy\PhpUnit {
    if (!class_exists(ProphecyTrait::class)) {
        trait ProphecyTrait {}
    }
}

namespace Phpactor\Completion\Tests {

    use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
    use PHPUnit\Framework\TestCase as PhpUnitTestCase;
    use Prophecy\PhpUnit\ProphecyTrait;


    class TestCase extends PhpUnitTestCase
    {
        use ArraySubsetAsserts;
        use ProphecyTrait;
    }
}
