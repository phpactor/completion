<?php

namespace Phpactor\Completion\Tests;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class TestCase extends PhpUnitTestCase
{
    use ArraySubsetAsserts;
    use ProphecyTrait;
}
