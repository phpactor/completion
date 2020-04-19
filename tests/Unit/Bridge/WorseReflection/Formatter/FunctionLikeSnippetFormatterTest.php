<?php

namespace Phpactor\Completion\Tests\Unit\Bridge\WorseReflection\Formatter;

use ArrayIterator;
use Phpactor\Completion\Bridge\WorseReflection\Formatter\FunctionLikeSnippetFormatter;
use Phpactor\Completion\Core\Formatter\ObjectFormatter;
use Phpactor\Completion\Tests\TestCase;
use Phpactor\WorseReflection\Core\DefaultValue;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionParameterCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunction;
use Phpactor\WorseReflection\Core\Reflection\ReflectionFunctionLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;
use Prophecy\PhpUnit\ProphecyTrait;

final class FunctionLikeSnippetFormatterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider provideReflectionToFormat
     */
    public function testFormat(ReflectionFunctionLike $reflection, string $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->format($reflection)
        );
    }

    public function provideReflectionToFormat(): iterable
    {
        yield 'Function without parameters' => [
            $this->reflectFunction('func', []),
            'func()',
        ];

        yield 'Function with mandatory parameters' => [
            $this->reflectFunction('func', [
                $this->parameter('test'),
                $this->parameter('i'),
            ]),
            'func(${1:\$test}, ${2:\$i})${0}'
        ];

        yield 'Function with mandatory and optional parameters' => [
            $this->reflectFunction('func', [
                $this->parameter('test'),
                $this->parameter('i', true),
            ]),
            'func(${1:\$test})${0}'
        ];

        yield 'Function with only optional parameters' => [
            $this->reflectFunction('func', [
                $this->parameter('test', true),
                $this->parameter('i', true),
            ]),
            'func(${1})${0}'
        ];

        yield 'Method without parameters' => [
            $this->reflectMethod('method', []),
            'method()'
        ];

        yield 'Method with mandatory parameters' => [
            $this->reflectFunction('method', [
                $this->parameter('test'),
                $this->parameter('i'),
            ]),
            'method(${1:\$test}, ${2:\$i})${0}'
        ];

        yield 'Method with mandatory and optional parameters' => [
            $this->reflectFunction('method', [
                $this->parameter('test'),
                $this->parameter('i', true),
            ]),
            'method(${1:\$test})${0}'
        ];

        yield 'Method with only optional parameters' => [
            $this->reflectFunction('method', [
                $this->parameter('test', true),
                $this->parameter('i', true),
            ]),
            'method(${1})${0}'
        ];
    }

    private function format(ReflectionFunctionLike $reflection): string
    {
        return (new FunctionLikeSnippetFormatter())
            ->format(new ObjectFormatter([]), $reflection)
        ;
    }

    private function parameter(string $name, bool $hasDefaultValue = false)
    {
        $defaultValue = $hasDefaultValue
            ? DefaultValue::fromValue('test')
            : DefaultValue::undefined()
        ;

        $parameter = $this->prophesize(ReflectionParameter::class);
        $parameter->name()->willReturn($name);
        $parameter->default()->willReturn($defaultValue);

        return $parameter->reveal();
    }

    private function reflectFunctionLike(
        string $reflectionFunctionLikeFqcn,
        string $name,
        array $parameters = []
    ): ReflectionFunctionLike {
        $parameterCollection = $this->prophesize(ReflectionParameterCollection::class);
        $parameterCollection->getIterator()->willReturn(new ArrayIterator($parameters));
        $parameterCollection->count()->willReturn(\count($parameters));

        $function = $this->prophesize($reflectionFunctionLikeFqcn);
        $function->name()->willReturn(Name::fromString($name));
        $function->parameters()->willReturn($parameterCollection->reveal());

        return $function->reveal();
    }

    private function reflectFunction(string $name, array $parameters = [])
    {
        return $this->reflectFunctionLike(ReflectionFunction::class, $name, $parameters);
    }

    private function reflectMethod(string $name, array $parameters = [])
    {
        return $this->reflectFunctionLike(ReflectionMethod::class, $name, $parameters);
    }
}
